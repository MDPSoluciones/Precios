# Comandos de Git - Chuleta rápida

Guía de referencia para el día a día con este repositorio (`MDPSoluciones`).

## 1. Antes de empezar a trabajar

| Comando | Qué hace |
|---|---|
| `git status` | Muestra en qué rama estás y qué archivos cambiaste (sin commitear). Usalo siempre antes de hacer cualquier otra cosa. |
| `git pull` | Trae los últimos cambios de GitHub y los mezcla en tu carpeta local. Hacelo antes de empezar a editar. |
| `git branch` | Lista las ramas locales y marca con `*` en cuál estás parado. |
| `git log --oneline -10` | Muestra los últimos 10 commits, uno por línea. |

## 2. Guardar tus cambios (workflow normal)

| Comando | Qué hace |
|---|---|
| `git status` | Ver qué archivos modificaste, agregaste o borraste. |
| `git diff` | Ver línea por línea qué cambiaste dentro de los archivos (antes de agregar). |
| `git add .` | Agrega TODOS los cambios (nuevos, modificados, borrados) al "área de preparación" (staging). |
| `git add nombre_archivo` | Agrega solo ese archivo puntual, si no querés subir todo. |
| `git commit -m "mensaje breve"` | Guarda los cambios agregados como un punto en la historia, con una descripción. |
| `git push origin main` | Sube los commits locales a GitHub, a la rama `main`. |

**Orden típico:** `git status` → `git add .` → `git commit -m "..."` → `git push origin main`

## 3. Ramas

| Comando | Qué hace |
|---|---|
| `git switch nombre_rama` | Cambia a otra rama (falla si tenés cambios sin commitear que chocan). |
| `git switch -c nombre_rama` | Crea una rama nueva y te cambia a ella. |
| `git branch -a` | Lista todas las ramas, incluidas las que están en GitHub (`remotes/origin/...`). |
| `git push origin main:MDP_Precios_cloud_Prod` | Actualiza la rama `MDP_Precios_cloud_Prod` en GitHub con lo que hay en tu `main` local. Repetir cambiando el nombre para `MDP_Precios_cloud_Labo` y `MDP_Precios_cloud`. |

## 4. Deshacer cosas (con cuidado)

| Comando | Qué hace |
|---|---|
| `git restore nombre_archivo` | Descarta cambios sin commitear en ese archivo (vuelve a como estaba en el último commit). **Se pierde lo editado, no se puede deshacer.** |
| `git restore --staged nombre_archivo` | Saca un archivo del área de preparación (staging) sin borrar el cambio, solo lo "des-agrega". |
| `git log --oneline` | Para ver el historial y encontrar a qué commit querés volver, si hace falta. |

## 5. Diagnóstico si algo anda raro

| Comando | Qué hace |
|---|---|
| `git status` | Primer paso siempre: te dice si hay cambios pendientes o conflictos. |
| `git remote -v` | Muestra a qué repositorio de GitHub está conectada la carpeta. |
| `git fsck --full` | Revisa si hay archivos internos de git corruptos (lo que nos pasó el 26/08). |

## Reglas de oro

1. Nunca reemplaces o copies archivos a mano por fuera de git — todo cambio pasa por `add` + `commit`.
2. Commits chicos y frecuentes, no dejar que se acumulen cientos de cambios sin commitear.
3. Trabajá solo en esta carpeta (`MDPSoluciones`), no mantengas copias paralelas.
4. Antes de cambiar de rama o hacer algo importante, corré `git status` primero.
