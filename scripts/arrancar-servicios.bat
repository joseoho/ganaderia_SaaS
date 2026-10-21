@echo off
rem ============================================================
rem  Arranque de los servicios del sistema ganadero
rem
rem  Levanta Apache (que sirve PHP 8.4 por CGI) y MariaDB de
rem  XAMPP, pero solo si no estan ya corriendo. Despues comprueba
rem  que los puertos escuchen y que la aplicacion responda.
rem
rem  Uso:
rem    arrancar-servicios.bat       muestra el resultado y espera
rem    arrancar-servicios.bat /q    sin pausa (Programador de tareas)
rem
rem  Codigo de salida: 0 = todo arriba, 1 = algo fallo
rem ============================================================
setlocal EnableExtensions

rem --- Rutas de esta maquina (si cambias de version de XAMPP o de PHP, se tocan aqui)
set "XAMPP=C:\xampp"
set "HTTPD=%XAMPP%\apache\bin\httpd.exe"
set "MYSQLD=%XAMPP%\mysql\bin\mysqld.exe"
set "MYINI=%XAMPP%\mysql\bin\my.ini"
set "LOG=%XAMPP%\logs\arranque-ganaderia.log"
set "URL=http://ganaderia_SaaS.test/login"
set "ESPERA=40"
set "FALLO=0"

if not exist "%XAMPP%\logs" mkdir "%XAMPP%\logs" >nul 2>&1

call :log "=== Arranque solicitado ==="

if not exist "%HTTPD%" (
    call :log "ERROR: no existe %HTTPD%"
    set "FALLO=1"
    goto :fin
)
if not exist "%MYSQLD%" (
    call :log "ERROR: no existe %MYSQLD%"
    set "FALLO=1"
    goto :fin
)
if not exist "%MYINI%" (
    call :log "ERROR: no existe %MYINI%"
    set "FALLO=1"
    goto :fin
)

rem --- Apache -------------------------------------------------
call :escucha 80
if errorlevel 1 (
    call :log "Apache: no estaba corriendo, se arranca"
    powershell -NoProfile -Command "Start-Process -FilePath '%HTTPD%' -WorkingDirectory '%XAMPP%\apache\bin' -WindowStyle Hidden"
) else (
    call :log "Apache: ya estaba corriendo (puerto 80)"
)

rem --- MariaDB ------------------------------------------------
call :escucha 3306
if errorlevel 1 (
    call :log "MariaDB: no estaba corriendo, se arranca"
    powershell -NoProfile -Command "Start-Process -FilePath '%MYSQLD%' -ArgumentList '--defaults-file=%MYINI%','--standalone' -WorkingDirectory '%XAMPP%\mysql\bin' -WindowStyle Hidden"
) else (
    call :log "MariaDB: ya estaba corriendo (puerto 3306)"
)

rem --- Esperar a que los dos puertos escuchen ----------------
set /a intentos=0
:esperar
call :escucha 80
if errorlevel 1 (set "OK80=0") else (set "OK80=1")
call :escucha 3306
if errorlevel 1 (set "OK3306=0") else (set "OK3306=1")
if "%OK80%%OK3306%"=="11" goto :listos

set /a intentos+=1
if %intentos% geq %ESPERA% (
    call :log "ERROR: tras %ESPERA% s no subieron (Apache/80: %OK80%, MariaDB/3306: %OK3306%)"
    set "FALLO=1"
    goto :fin
)
rem ping en vez de timeout: timeout falla sin consola (tareas programadas)
ping -n 2 127.0.0.1 >nul
goto :esperar

:listos
call :log "Puertos 80 y 3306 escuchando"

rem --- Comprobar que la aplicacion responde ------------------
where curl.exe >nul 2>&1
if errorlevel 1 (
    call :log "Aviso: no hay curl, no se comprueba la URL"
    goto :fin
)

set "CODIGO="
for /f %%c in ('curl.exe -s -o nul -w "%%{http_code}" --max-time 15 "%URL%" 2^>nul') do set "CODIGO=%%c"

if "%CODIGO%"=="200" (
    call :log "La aplicacion responde 200 en %URL%"
) else if "%CODIGO%"=="302" (
    call :log "La aplicacion responde 302 (redirige al login) en %URL%"
) else (
    call :log "ERROR: la aplicacion responde '%CODIGO%' en %URL%"
    set "FALLO=1"
)

:fin
if "%FALLO%"=="1" (
    call :log "=== Terminado CON ERRORES ==="
) else (
    call :log "=== Terminado: todo arriba ==="
)

if /i not "%~1"=="/q" (
    echo.
    echo Log: %LOG%
    pause
)

exit /b %FALLO%

rem ============================================================
rem  Subrutinas
rem ============================================================

:escucha
rem %1 = puerto. Sale 0 si hay algo escuchando, 1 si no.
rem Se usa PowerShell y no netstat porque netstat traduce
rem LISTENING como ESCUCHANDO en Windows en espanol.
powershell -NoProfile -Command "if (Get-NetTCPConnection -LocalPort %1 -State Listen -ErrorAction SilentlyContinue) { exit 0 } else { exit 1 }" >nul 2>&1
exit /b %errorlevel%

:log
rem %1 = mensaje. Escribe en pantalla y en el log con fecha y hora.
>>"%LOG%" echo [%DATE% %TIME%] %~1
echo %~1
exit /b 0
