use std::process::Command;
use std::os::windows::process::CommandExt; // For creation_flags if needed, but simple Command is fine

#[tauri::command]
fn get_printers() -> Result<Vec<String>, String> {
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

#[tauri::command]
fn print_job(printer_name: String, content: String) -> Result<String, String> {
    // Using Out-Printer. Note: This assumes 'content' is text.
    // For raw bytes (ESC/POS), we'd need a different approach (RawPrint crate or writing to \\localhost\Share).
    // But for now, text-based printing is the request for "Direct Print".
    
    // We pass content via pipeline to avoid command line length limits/escaping issues
    // `echo "content" | Out-Printer -Name "printer_name"`
    
    // Safety: Escape quotes in content? Or better, write to temp file.
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

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    tauri::Builder::default()
        .plugin(tauri_plugin_sql::Builder::default().build())
        .plugin(tauri_plugin_shell::init())
        .invoke_handler(tauri::generate_handler![get_printers, print_job])
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
