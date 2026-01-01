use std::process::Command;
use std::fs;
use std::io::Write;
use tauri::Manager;
#[tauri::command]
fn get_printers() -> Result<Vec<String>, String> {
    #[cfg(target_os = "windows")]
    {
        let output = Command::new("powershell")
            .args(["-NoProfile", "-Command", "Get-Printer | Select-Object -ExpandProperty Name"])
            .output()
            .map_err(|e| e.to_string())?;

        if !output.status.success() {
            return Err(String::from_utf8_lossy(&output.stderr).to_string());
        }

        let stdout = String::from_utf8_lossy(&output.stdout);
        let printers: Vec<String> = stdout
            .lines()
            .map(|line| line.trim().to_string())
            .filter(|line| !line.is_empty())
            .collect();

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

#[tauri::command]
fn print_job(printer_name: String, content: String) -> Result<String, String> {
    #[cfg(target_os = "windows")]
    {
        let temp_dir = std::env::temp_dir();
        let file_path = temp_dir.join("pos_print_job.txt");
        std::fs::write(&file_path, &content).map_err(|e| e.to_string())?;

        let cmd_str = format!("Get-Content '{}' | Out-Printer -Name '{}'", file_path.display(), printer_name);

        let output = Command::new("powershell")
            .args(["-NoProfile", "-Command", &cmd_str])
            .output()
            .map_err(|e| e.to_string())?;

        if !output.status.success() {
            return Err(String::from_utf8_lossy(&output.stderr).to_string());
        }

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
            download_image,
            get_image_path,
            clear_image_cache,
            get_cache_size
        ])
        .setup(|app| {
            if cfg!(debug_assertions) {
                app.handle().plugin(
                    tauri_plugin_log::Builder::default()
                        .level(log::LevelFilter::Info)
                        .build(),
                )?;
            }
            Ok(())
        })
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
