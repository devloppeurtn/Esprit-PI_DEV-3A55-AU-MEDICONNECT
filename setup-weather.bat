@echo off
echo ========================================
echo Configuration de l'API Meteo
echo ========================================
echo.
echo Ce script va vous aider a configurer l'API meteo OpenWeatherMap
echo.
echo Etape 1: Ouvrir le site d'inscription
echo ----------------------------------------
echo Je vais ouvrir le site OpenWeatherMap dans votre navigateur...
timeout /t 2 >nul
start https://home.openweathermap.org/users/sign_up
echo.
echo Veuillez:
echo 1. Creer un compte sur OpenWeatherMap
echo 2. Verifier votre email
echo 3. Vous connecter et aller dans "API keys"
echo 4. Copier votre cle API
echo.
pause
echo.
echo Etape 2: Entrer votre cle API
echo ----------------------------------------
set /p API_KEY="Collez votre cle API ici: "
echo.
echo Etape 3: Configuration du fichier .env
echo ----------------------------------------
echo Mise a jour du fichier .env...

powershell -Command "(Get-Content .env) -replace 'OPENWEATHER_API_KEY=.*', 'OPENWEATHER_API_KEY=%API_KEY%' | Set-Content .env"

echo.
echo ========================================
echo Configuration terminee avec succes!
echo ========================================
echo.
echo Votre cle API a ete configuree: %API_KEY%
echo.
echo Prochaines etapes:
echo 1. Redemarrez le serveur Symfony
echo 2. Rafraichissez la page des evenements
echo 3. La meteo reelle s'affichera maintenant!
echo.
pause
