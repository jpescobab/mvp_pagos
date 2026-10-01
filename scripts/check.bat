@echo off
if not exist AGENTS.md exit /b 1
if not exist CLAUDE.md exit /b 1
if not exist HARNESS_IA.md exit /b 1
if not exist openspec\config.yaml exit /b 1
if not exist docs\DECISIONES_TOMADAS_v9.md exit /b 1
echo Estructura OpenSpec/Harness OK
