use std::process::Command;
use std::fs;
use std::io::Write;
use tauri::Manager;

// Windows-specific imports for hiding console windows
#[cfg(target_os = "windows")]
use std::os::windows::process::CommandExt;

#[cfg(target_os = "windows")]
const CREATE_NO_WINDOW: u32 = 0x08000000;
#[tauri::command]
fn get_printers() -> Result<Vec<String>, String> {
    log::info!("Fetching available printers...");
    
    #[cfg(target_os = "windows")]
    {
        let mut cmd = Command::new("powershell");
        cmd.creation_flags(CREATE_NO_WINDOW)
            .args(["-NoProfile", "-Command", "Get-Printer | Select-Object -ExpandProperty Name"]);
        
        let output = cmd.output()
            .map_err(|e| {
                log::error!("Failed to execute PowerShell for get_printers: {}", e);
                e.to_string()
            })?;

        if !output.status.success() {
            let error = String::from_utf8_lossy(&output.stderr).to_string();
            log::error!("PowerShell get_printers failed: {}", error);
            return Err(error);
        }

        let stdout = String::from_utf8_lossy(&output.stdout);
        let printers: Vec<String> = stdout
            .lines()
            .map(|line| line.trim().to_string())
            .filter(|line| !line.is_empty())
            .collect();

        log::info!("Found {} printers: {:?}", printers.len(), printers);
        Ok(printers)
    }

    #[cfg(not(target_os = "windows"))]
    {
        // MacOS/Linux implementation using lpstat
        let output = Command::new("lpstat")
            .arg("-e") // List all printers
            .output()
            .map_err(|e| e.to_string())?;

        if !output.status.success() {
            // Check if lpstat exists, if not return generic error or empty
            return Ok(vec!["Mock Printer (Dev)".to_string()]); 
        }

        let stdout = String::from_utf8_lossy(&output.stdout);
        let printers: Vec<String> = stdout
            .lines()
            .map(|line| line.trim().to_string())
            .filter(|line| !line.is_empty())
            .collect();

        Ok(printers)
    }
}

#[derive(serde::Deserialize)]
struct PrintReceiptData {
    printer_name: String,
    template: String,
    data: serde_json::Value,
    paper_width: String, // "58mm" or "80mm"
    cpl: Option<u32>,
}

#[tauri::command]
fn print_receipt(receipt_data: PrintReceiptData) -> Result<String, String> {
    // Calculate characters per line
    let chars = receipt_data.cpl.unwrap_or(
        if receipt_data.paper_width == "80mm" { 48 } else { 32 }
    ) as usize;
    
    // Render as plain text (Windows driver will handle font)
    let mut output = String::new();
    
    // Process template line by line
    let lines: Vec<&str> = receipt_data.template.lines().collect();
    
    for line in lines {
        if line.trim().is_empty() {
            output.push_str("\n");
            continue;
        }
        
        // Process template variables
        let processed = process_template_line(line, &receipt_data.data, chars)?;
        
        // Handle formatting tags with plain text spacing
        if processed.starts_with("{{c:") && processed.ends_with("}}") {
            // Center align
            let content = processed.trim_start_matches("{{c:").trim_end_matches("}}");
            let spaces = chars.saturating_sub(content.len()) / 2;
            output.push_str(&" ".repeat(spaces));
            output.push_str(content);
            output.push_str("\n");
        } else if processed.starts_with("{{r:") && processed.ends_with("}}") {
            // Right align
            let content = processed.trim_start_matches("{{r:").trim_end_matches("}}");
            let spaces = chars.saturating_sub(content.len());
            output.push_str(&" ".repeat(spaces));
            output.push_str(content);
            output.push_str("\n");
        } else if processed.contains("{{lr:") {
            // Left-right justify
            let content = processed.trim_start_matches("{{lr:").trim_end_matches("}}");
            if let Some(pipe_pos) = content.find('|') {
                let left = content[..pipe_pos].trim();
                let right = content[pipe_pos + 1..].trim();
                let spaces = chars.saturating_sub(left.len() + right.len());
                output.push_str(left);
                output.push_str(&" ".repeat(spaces));
                output.push_str(right);
                output.push_str("\n");
            } else {
                output.push_str(&processed);
                output.push_str("\n");
            }
        } else if processed == "{{line}}" {
            // Print line separator
            output.push_str(&"-".repeat(chars));
            output.push_str("\n");
        } else if processed.starts_with("{{b:") && processed.ends_with("}}") {
            // Bold - just print as plain text (no control over formatting)
            let content = processed.trim_start_matches("{{b:").trim_end_matches("}}");
            output.push_str(content);
            output.push_str("\n");
        } else if processed.starts_with("{{size:") {
            // Size - just print as plain text (no control over formatting)
            if let Some(colon_pos) = processed[7..].find(':') {
                let content = processed[7 + colon_pos + 1..].trim_end_matches("}}");
                output.push_str(content);
                output.push_str("\n");
            } else {
                output.push_str(&processed);
                output.push_str("\n");
            }
        } else {
            output.push_str(&processed);
            output.push_str("\n");
        }
    }
    
    // Add blank lines for spacing
    output.push_str("\n\n\n");
    
    // Write to temp file and print using PowerShell
    use std::fs;
    use std::process::Command;
    
    let temp_dir = std::env::temp_dir();
    let file_path = temp_dir.join("pos_print_job.txt");
    
    fs::write(&file_path, output.as_bytes())
        .map_err(|e| format!("Failed to write temp file: {}", e))?;

    let cmd_str = format!(
        "Get-Content '{}' | Out-Printer -Name '{}'",
        file_path.display(),
        receipt_data.printer_name
    );

    log::info!("Printing receipt to: {}", receipt_data.printer_name);
    
    #[cfg(target_os = "windows")]
    let mut cmd = Command::new("powershell");
    #[cfg(target_os = "windows")]
    cmd.creation_flags(CREATE_NO_WINDOW);
    
    #[cfg(not(target_os = "windows"))]
    let mut cmd = Command::new("powershell");
    
    let cmd_output = cmd
        .args(["-NoProfile", "-Command", &cmd_str])
        .output()
        .map_err(|e| {
            log::error!("Failed to execute PowerShell for print_receipt: {}", e);
            format!("Failed to execute PowerShell: {}", e)
        })?;

    if !cmd_output.status.success() {
        let error = String::from_utf8_lossy(&cmd_output.stderr).to_string();
        log::error!("Print receipt failed: {}", error);
        return Err(error);
    }
    
    log::info!("Successfully printed receipt to {}", receipt_data.printer_name);
    
    Ok(format!("Printed to {}", receipt_data.printer_name))
}

fn process_template_line(line: &str, data: &serde_json::Value, _chars: usize) -> Result<String, String> {
    let mut result = line.to_string();
    
    // Create date string with proper lifetime
    let date_str = chrono::Local::now().format("%d/%m/%Y %H:%M").to_string();
    
    // Replace simple variables
    let replacements = vec![
        ("{{store_name}}", data.get("store_name").and_then(|v| v.as_str()).unwrap_or("RESTO POS")),
        ("{{store_address}}", data.get("store_address").and_then(|v| v.as_str()).unwrap_or("")),
        ("{{store_phone}}", data.get("store_phone").and_then(|v| v.as_str()).unwrap_or("")),
        ("{{footer}}", data.get("receipt_footer").and_then(|v| v.as_str()).unwrap_or("Terima Kasih")),
        ("{{date}}", date_str.as_str()),
        ("{{invoice_number}}", data.get("invoice_number").and_then(|v| v.as_str()).unwrap_or("-")),
        ("{{cashier_name}}", data.get("cashier_name").and_then(|v| v.as_str()).unwrap_or("Admin")),
        ("{{table_number}}", data.get("table_number").and_then(|v| v.as_str()).unwrap_or("-")),
    ];
    
    for (key, value) in replacements {
        result = result.replace(key, value);
    }
    
    // Handle numeric values
    if let Some(subtotal) = data.get("subtotal").and_then(|v| v.as_f64()) {
        result = result.replace("{{subtotal}}", &format!("{:.0}", subtotal));
    }
    if let Some(tax) = data.get("tax").and_then(|v| v.as_f64()) {
        result = result.replace("{{tax}}", &format!("{:.0}", tax));
    }
    if let Some(total) = data.get("total").and_then(|v| v.as_f64()) {
        result = result.replace("{{total}}", &format!("{:.0}", total));
    }
    if let Some(discount) = data.get("discount").and_then(|v| v.as_f64()) {
        if discount > 0.0 {
            result = result.replace("{{discount}}", &format!("({:.0})", discount));
            result = result.replace("{{discount_label}}", "Diskon:");
        } else {
            result = result.replace("{{discount}}", "");
            result = result.replace("{{discount_label}}", "");
        }
    }
    
    // Handle items list
    if result.contains("{{items}}") {
        if let Some(items) = data.get("items").and_then(|v| v.as_array()) {
            let mut items_text = String::new();
            let is_ticket = data.get("is_ticket").and_then(|v| v.as_bool()).unwrap_or(false);
            
            for item in items {
                let qty = item.get("quantity").and_then(|v| v.as_i64()).unwrap_or(0);
                let name = item.get("product_name")
                    .or_else(|| item.get("product").and_then(|p| p.get("name")))
                    .and_then(|v| v.as_str())
                    .unwrap_or("Unknown");
                
                if is_ticket {
                    items_text.push_str(&format!("{} x {}\n", qty, name));
                    if let Some(notes) = item.get("notes").and_then(|v| v.as_str()) {
                        if !notes.is_empty() {
                            items_text.push_str(&format!("   (Catatan: {})\n", notes));
                        }
                    }
                } else {
                    let price = item.get("price").and_then(|v| v.as_f64()).unwrap_or(0.0);
                    let subtotal = item.get("subtotal").and_then(|v| v.as_f64()).unwrap_or(0.0);
                    items_text.push_str(&format!("{}\n", name));
                    items_text.push_str(&format!("{} x {:.0}                {:.0}\n", qty, price, subtotal));
                }
            }
            result = result.replace("{{items}}", items_text.trim_end());
        }
    }
    
    Ok(result)
}

#[tauri::command]
fn print_job(printer_name: String, content: String) -> Result<String, String> {
    #[cfg(target_os = "windows")]
    {
        let temp_dir = std::env::temp_dir();
        let file_path = temp_dir.join("pos_print_job.txt");
        std::fs::write(&file_path, &content).map_err(|e| e.to_string())?;

        let cmd_str = format!("Get-Content '{}' | Out-Printer -Name '{}'", file_path.display(), printer_name);

        log::info!("Printing job to: {}", printer_name);
        
        let mut cmd = Command::new("powershell");
        cmd.creation_flags(CREATE_NO_WINDOW);
        
        let output = cmd
            .args(["-NoProfile", "-Command", &cmd_str])
            .output()
            .map_err(|e| {
                log::error!("Failed to execute PowerShell for print_job: {}", e);
                e.to_string()
            })?;

        if !output.status.success() {
            let error = String::from_utf8_lossy(&output.stderr).to_string();
            log::error!("Print job failed: {}", error);
            return Err(error);
        }
        
        log::info!("Successfully printed job to {}", printer_name);

        Ok("Printed successfully".to_string())
    }

    #[cfg(not(target_os = "windows"))]
    {
        // MacOS/Linux simple lp implementation
        use std::io::Write;
        
        // If we want to pipe content, we need to spawn, write to stdin, then wait.
        // Simplified approach using child process with piped input:
        let mut child = Command::new("lp")
            .arg("-d")
            .arg(&printer_name)
            .stdin(std::process::Stdio::piped())
            .spawn()
            .map_err(|e| e.to_string())?;

        if let Some(mut stdin) = child.stdin.take() {
            stdin.write_all(content.as_bytes()).map_err(|e| e.to_string())?;
        }

        let output = child.wait_with_output().map_err(|e| e.to_string())?;

        if !output.status.success() {
            return Err(String::from_utf8_lossy(&output.stderr).to_string());
        }

        Ok("Printed successfully (Unix)".to_string())
    }
}

// IMAGE CACHING COMMANDS

#[tauri::command]
async fn download_image(app: tauri::AppHandle, url: String, filename: String) -> Result<String, String> {
    // Get app data directory
    let app_data_dir = app.path().app_data_dir()
        .map_err(|e| format!("Failed to get app data dir: {}", e))?;
    
    let images_dir = app_data_dir.join("images");
    
    // Create images directory if it doesn't exist
    fs::create_dir_all(&images_dir)
        .map_err(|e| format!("Failed to create images dir: {}", e))?;
    
    let file_path = images_dir.join(&filename);
    
    // Skip if file already exists
    if file_path.exists() {
        return Ok(format!("Image already cached: {}", filename));
    }
    
    // Download image
    let response = reqwest::get(&url).await
        .map_err(|e| format!("Failed to download image: {}", e))?;
    
    if !response.status().is_success() {
        return Err(format!("HTTP error: {}", response.status()));
    }
    
    let bytes = response.bytes().await
        .map_err(|e| format!("Failed to read image bytes: {}", e))?;
    
    // Save to file
    let mut file = fs::File::create(&file_path)
        .map_err(|e| format!("Failed to create file: {}", e))?;
    
    file.write_all(&bytes)
        .map_err(|e| format!("Failed to write file: {}", e))?;
    
    Ok(format!("Downloaded: {}", filename))
}

#[tauri::command]
fn get_image_path(app: tauri::AppHandle, filename: String) -> Result<String, String> {
    let app_data_dir = app.path().app_data_dir()
        .map_err(|e| format!("Failed to get app data dir: {}", e))?;
    
    let file_path = app_data_dir.join("images").join(&filename);
    
    if file_path.exists() {
        Ok(file_path.to_string_lossy().to_string())
    } else {
        Err(format!("Image not found: {}", filename))
    }
}

#[tauri::command]
fn clear_image_cache(app: tauri::AppHandle) -> Result<String, String> {
    let app_data_dir = app.path().app_data_dir()
        .map_err(|e| format!("Failed to get app data dir: {}", e))?;
    
    let images_dir = app_data_dir.join("images");
    
    if images_dir.exists() {
        fs::remove_dir_all(&images_dir)
            .map_err(|e| format!("Failed to remove images dir: {}", e))?;
        
        // Recreate empty directory
        fs::create_dir_all(&images_dir)
            .map_err(|e| format!("Failed to recreate images dir: {}", e))?;
    }
    
    Ok("Image cache cleared".to_string())
}

#[tauri::command]
fn get_cache_size(app: tauri::AppHandle) -> Result<u64, String> {
    let app_data_dir = app.path().app_data_dir()
        .map_err(|e| format!("Failed to get app data dir: {}", e))?;
    
    let images_dir = app_data_dir.join("images");
    
    if !images_dir.exists() {
        return Ok(0);
    }
    
    let mut total_size: u64 = 0;
    
    if let Ok(entries) = fs::read_dir(&images_dir) {
        for entry in entries.flatten() {
            if let Ok(metadata) = entry.metadata() {
                if metadata.is_file() {
                    total_size += metadata.len();
                }
            }
        }
    }
    
    Ok(total_size)
}

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    tauri::Builder::default()
        .plugin(tauri_plugin_sql::Builder::default().build())
        .plugin(tauri_plugin_shell::init())
        .invoke_handler(tauri::generate_handler![
            get_printers, 
            print_job,
            print_receipt,
            download_image,
            get_image_path,
            clear_image_cache,
            get_cache_size
        ])
        .setup(|app| {
            // Setup logging for both debug and release builds
            // In production, logs will be written to a file in app data directory
            let log_plugin = tauri_plugin_log::Builder::default()
                .level(log::LevelFilter::Info);
            
            // In production, write to file
            #[cfg(not(debug_assertions))]
            let log_plugin = log_plugin
                .target(tauri_plugin_log::Target::new(
                    tauri_plugin_log::TargetKind::LogDir { file_name: Some("resto-pos.log".to_string()) }
                ))
                .rotation_strategy(tauri_plugin_log::RotationStrategy::KeepAll)
                .max_file_size(5_000_000); // 5MB per file
            
            app.handle().plugin(log_plugin.build())?;
            
            log::info!("=== Resto POS Application Started ===");
            log::info!("Version: {}", app.package_info().version);
            log::info!("Debug mode: {}", cfg!(debug_assertions));
            
            Ok(())
        })
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
