use std::process::Command;
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
