@echo off
SET REPO_URL=https://github.com/gennaroparziale/stockflow.git

echo ================================
echo Inizializzazione Git
echo ================================
git init

echo ================================
echo Aggiunta file al commit
echo ================================
git add -A

echo ================================
echo Commit forzato
echo ================================
git commit -m "Primo commit - caricamento iniziale"

echo ================================
echo Imposto il branch su 'main'
echo ================================
git branch -M main

echo ================================
echo Configuro remote origin
echo ================================
git remote remove origin 2>nul
git remote add origin %REPO_URL%

echo ================================
echo Eseguo il push verso GitHub
echo ================================
git push -u origin main

echo ================================
echo ✅ Caricamento completato
echo ================================
pause
