@echo off
title Mercure Hub
echo =======================================================
echo.
echo Dang khoi dong Mercure Hub tai http://localhost:9999...
echo.
echo =======================================================

:: Thiet lap cac bien moi truong
set MERCURE_PUBLISHER_JWT_KEY=071c26246473e3a27780cfddf6123f5df4116fb8fc6f1456e04fabbeb8dd193a
set MERCURE_SUBSCRIBER_JWT_KEY=071c26246473e3a27780cfddf6123f5df4116fb8fc6f1456e04fabbeb8dd193a
set SERVER_NAME=:9999
set CORS_ALLOWED_ORIGINS=*

:: Chay file thuc thi
cd mercure\win
mercure.exe run --config dev.Caddyfile

pause