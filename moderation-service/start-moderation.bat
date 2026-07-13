@echo off
rem uLam NSFWJS moderation fallback — starts minimized and stays running.
rem To auto-start on login: press Win+R, type  shell:startup  , and put a
rem shortcut to this file in the folder that opens.
cd /d "%~dp0"
start "uLam Moderation" /min cmd /c "node server.js"
