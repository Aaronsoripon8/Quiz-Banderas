# Quiz de Banderes

Resum
-----
Aquest projecte és una aplicació web lleugera per practicar i avaluar coneixements sobre banderes del món. Està pensada com un quiz ràpid i accessible que es pot utilitzar tant en dispositius mòbils com a escriptori.

Característiques principals
---------------------------
- Partida ràpida: per defecte el quiz sol·licita 10 preguntes al servidor i el jugador disposa de 30 segons per completar la partida (mode "F1")
- Presentació de preguntes: cada pregunta mostra un títol (per exemple "De quin país és aquesta bandera?"), una imatge gran de la bandera i quatre opcions de resposta disposades en una graella 2x2
- Feedback immediat: al seleccionar una resposta el botó es coloreja en verd si és correcta o en vermell si és incorrecta; la resposta correcta es marca en verd
- Ordre d'opcions estable: l'ordre de les opcions es barreja una sola vegada per pregunta al client perquè no canviï si la interfície es tornés a renderitzar
- Temporitzador: el temporitzador de 30 segons és global per a la partida (no per pregunta). Quan s'acaba el temps, les opcions es desactiven i apareix el botó per enviar resultats
- Experiència single-page: l'experiència és d'una sola pàgina: en prémer "Començar" desapareix el formulari del nom i apareix el quiz sense recarregar la pàgina
- Guardat de puntuacions: s'intenta guardar a la base de dades (taula `scores`) amb PDO; si la BD no està disponible les puntuacions s'emmagatzemen a `api/scores.json` com a fallback
- Classificació (Top 10): el rànquing mostra el millor resultat per nom (agrupat case-insensitive) i retorna el Top 10 ordenat per respostes correctes
- Administració: `api/admin.php` permet crear/editar/esborrar preguntes amb fins a 4 respostes per pregunta
- Responsive: el disseny adapta la graella a una única columna en pantalles petites per facilitar la interacció tàctil
- Guia d'estil JavaScript: el codi client s'ha format seguint convencions similars a JavaScript Standard Style (es recomana afegir `standard` com a linter)

Com funciona el joc (detalls)
-----------------------------
- Nombre de preguntes: per defecte es demanen 10 preguntes al servidor. La crida fetch està a `scripts.js`:

  ```js
  fetch('./api/getPreguntes.php?n=10')
  ```

  Pots canviar aquest `n` si vols partides més curtes o més llargues (tant a `scripts.js` com afegint suport al backend si cal)

- Temporitzador: el temporitzador global està a `scripts.js` en la variable `segonsRestants` i s'inicialitza a 30 segons. Per ajustar el límit modifica aquesta variable i/o la lògica a `iniciarTemporitzador()`:

  ```js
  let segonsRestants = 30 // canvia per exemple a 60 per tenir 60 segons
  ```

- Flux de la partida:
  1. L'usuari introdueix el seu nom i prem "Començar"
  2. El formulari s'oculta i apareix l'encapçalament de salutació amb un botó "Canviar nom"
  3. Es carreguen les preguntes (fetch a `getPreguntes.php`) i es barregen localment les respostes
  4. El temporitzador comença a comptar des de 30s
  5. L'usuari selecciona respostes; cada selecció bloqueja les opcions d'aquesta pregunta i aplica el color de feedback
  6. Si es responen totes les preguntes o s'esgota el temps, apareix el botó "Enviar Resultats"
  7. En enviar, el client envia les respostes a `api/finalitza.php` per validació al servidor i després a `api/guardar_puntuacio.php` per guardar la puntuació
  8. Es mostra la pantalla final amb el recompte de respostes correctes i la classificació (Top 10)

Interfície i accessibilitat
--------------------------
- Les respostes estan en botons grans (fàcils de tocar en mòbil)
- Hi ha outlines de focus accessibles per navegació amb teclat
- Els contrastos de color estan triats per ser llegibles; s'utilitza una paleta amb blau/accent i colors suaus per al feedback (verd/vermell)
- En mòbils la graella 2x2 passa a 1 columna per millorar la usabilitat tàctil

Puntuació i classificació
-------------------------
- L'aplicació envia al servidor un array amb objectes `{ idPregunta, resposta }`
- `finalitza.php` valida les respostes utilitzant la BD i retorna `{ total, correctes }`
- `guardar_puntuacio.php` intenta inserir a la taula `scores`; si falla guarda l'entrada a `api/scores.json` amb la data
- `get_scores.php` retorna el Top 10 agrupant per nom (sense distingir majúscules/minúscules) i retornant la millor puntuació per usuari

Admin — crear preguntes
----------------------
- `api/admin.php` té un formulari per crear o editar preguntes
- Cada pregunta emmagatzema les seves respostes com JSON a la columna `respostes` (ex.: `[{"id":1,"etiqueta":"Espanya"}, ...]`)
- El formulari d'administració accepta fins a 4 respostes per entrada; abans d'enviar, el client genera el JSON i l'envia al servidor

Executar localment
------------------
Requisits mínims:
- PHP 7.4+ amb PDO (MySQL/MariaDB)
- Navegador modern

Mètode ràpid (usar servidor PHP integrat):

1. Obre PowerShell a la carpeta del projecte (ex: `e:\Projecto 1`)
2. Executa el servidor PHP:

```powershell
php -S localhost:8000 -t .
```

3. Obre `http://localhost:8000/index.html` al navegador

Notes:
- El frontend fa peticions a `./api/*`, per tant el servidor ha de servir els fitxers PHP de la carpeta `api/`
- Si no tens PHP instal·lat, pots obrir `index.html` directament, però les funcionalitats que requereixen backend no funcionaran

Base de dades i taules (exemple)
-------------------------------
Si vols utilitzar la BD en lloc del fallback a fitxer, crea una base de dades i afegeix les taules següents (MySQL):

```sql
CREATE TABLE questions (
  id INT PRIMARY KEY,
  pregunta TEXT NOT NULL,
  imatge VARCHAR(1024),
  respostes JSON NOT NULL,
  resposta_correcta INT
);

CREATE TABLE scores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(255) NOT NULL,
  total INT NOT NULL,
  correctes INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Configuració de la connexió
---------------------------
Crea `api/config.php` (no està inclòs al repositori per seguretat) i defineix com a mínim:

```php
<?php
define('DB_DRIVER', 'mysql');
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'la_teva_basedades');
define('DB_USER', 'usuari');
define('DB_PASS', 'contrasenya');
define('DB_CHARSET', 'utf8mb4');
```

Proves i comprovacions
----------------------
- Prova manual: obre la web, inicia el quiz, respon preguntes i envia resultats. Revisa la consola/Network per veure les crides a `api/finalitza.php` i `api/guardar_puntuacio.php`
- Verifica que si la BD no està configurada es crea/actualitza `api/scores.json`

Suggeriments i passos següents
------------------------------
- Afegir autenticació al panell `api/admin.php` per evitar accés públic
- Afegir validació més estricta a l'admin per evitar IDs duplicats o respostes buides
- Afegir tests automatitzats (PHPUnit per al backend, Cypress/Playwright per al flux E2E)
- Afegir linter i hooks pre-commit (husky) per fer complir la guia d'estil


