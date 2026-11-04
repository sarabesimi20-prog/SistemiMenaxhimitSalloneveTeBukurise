# RAPORTI TEKNIK 2 – DATABAZA, BACKEND-I DHE API

**Projekti:** Sistem menaxhimi të salloneve të bukurisë

# 1. DATABAZA

## 1.1 Teknologjia dhe krijimi i databazës

Për zhvillimin e sistemit LUMIÈRE Beauty Studio është përdorur MySQL/MariaDB për ruajtjen dhe menaxhimin e të dhënave. Për administrimin e databazës është përdorur phpMyAdmin.

Emri i databazës është `salloni_bukuris`.

Databaza është krijuar me Storage Engine InnoDB, i cili mundëson përdorimin e çelësave të huaj (Foreign Key), relacioneve ndërmjet tabelave dhe ruajtjen e integritetit të të dhënave.

**Karakteristikat teknike:**

- DBMS: MariaDB 10.4.32
- PHP: 8.1.25
- Storage Engine: InnoDB
- Character Set: utf8mb4
- Collation: utf8mb4_unicode_ci / utf8mb4_general_ci

## 1.2 Struktura aktuale e databazës

Databaza përmban gjithsej 19 tabela, të organizuara sipas funksionaliteteve të sistemit.

| Nr. | Tabela | Përshkrimi |
|---|---|---|
| 1 | administratoret | Ruajtja e të dhënave të administratorëve |
| 2 | aktivitetet | Evidentimi i aktiviteteve në sistem |
| 3 | klientet | Ruajtja e të dhënave të klientëve |
| 4 | klient_shenime | Ruajtja e shënimeve për klientët |
| 5 | konfigurimet_studios | Konfigurimet e studios |
| 6 | kuponet | Menaxhimi i kuponëve të zbritjes |
| 7 | levizjet_stokut | Evidentimi i lëvizjeve të stokut |
| 8 | mungesat_stafit | Evidentimi i mungesave të stafit |
| 9 | njoftimet | Ruajtja e njoftimeve |
| 10 | orari_punes | Organizimi i orarit të punës |
| 11 | pagesa | Menaxhimi i pagesave |
| 12 | produktet | Menaxhimi i produkteve |
| 13 | promocionet | Menaxhimi i promocioneve |
| 14 | sherbimet | Ruajtja e shërbimeve dhe çmimeve |
| 15 | shpenzimet | Evidentimi i shpenzimeve |
| 16 | stafi | Ruajtja e të dhënave të punonjësve |
| 17 | terminet | Menaxhimi i rezervimeve dhe termineve |
| 18 | users | Menaxhimi i përdoruesve dhe roleve |
| 19 | vleresimet | Ruajtja e vlerësimeve të klientëve |

## 1.3 Tabelat kryesore dhe fushat e tyre

Databaza lokale `salloni_bukuris` përmban 19 tabela: `administratoret`, `aktivitetet`, `klientet`, `klient_shenime`, `konfigurimet_studios`, `kuponet`, `levizjet_stokut`, `mungesat_stafit`, `njoftimet`, `orari_punes`, `pagesa`, `produktet`, `promocionet`, `sherbimet`, `shpenzimet`, `stafi`, `terminet`, `users` dhe `vleresimet`.

Entitetet kryesore të sistemit janë klientët, stafi, shërbimet, terminet dhe pagesat. Tabelat e tjera mbështesin menaxhimin e produkteve, stokut, shpenzimeve, orarit të punës, mungesave, promocioneve, kuponëve, njoftimeve dhe aktiviteteve të sistemit.

Lidhjet kryesore ndërmjet tabelave janë:

- Klienti → terminet dhe shënimet e klientit.
- Stafi → terminet, orari i punës dhe mungesat.
- Shërbimi → terminet.
- Termini → pagesat dhe vlerësimet.
- Produkti → lëvizjet e stokut.
- Përdoruesi → aktivitetet dhe njoftimet.
- Përdoruesi → anëtari i stafit (lidhje opsionale).

Skema përdor çelësa primarë (`PRIMARY KEY`) për identifikimin unik të regjistrimeve dhe çelësa të huaj (`FOREIGN KEY`) për ruajtjen e lidhjeve ndërmjet tabelave. Kufizimet `UNIQUE` përdoren për email-in e përdoruesve dhe administratorëve, kodin e kuponit, lidhjen e shënimeve me klientin dhe kombinimin e stafit me ditën e javës në orarin e punës.

Gjithashtu përdoren fusha `NOT NULL`, vlera të parazgjedhura (`DEFAULT`) dhe fusha `ENUM` për kufizimin e statuseve dhe llojeve të caktuara.

Tabelat përdorin motorin InnoDB, i cili mundëson ruajtjen e integritetit referencial përmes lidhjeve ndërmjet të dhënave.

# 2. BACKEND-I

## 2.1 Lidhja e Backend-it me databazën

Backend-i i sistemit LUMIÈRE Beauty Studio është zhvilluar në PHP dhe përdor PDO (PHP Data Objects) për komunikimin me databazën MySQL/MariaDB.

Lidhja me databazën realizohet përmes skedarit `config/database.php`, ku përcaktohen parametrat e lidhjes, përfshirë host-in, emrin e databazës dhe karakteret e përdorura (`utf8mb4`).

Për konfigurimin e lidhjes përdoren këto mekanizma:

- `PDO::ERRMODE_EXCEPTION` – mundëson kapjen e gabimeve gjatë komunikimit me databazën.
- `PDO::FETCH_ASSOC` – kthen rezultatet në formë të vargjeve asociative.
- `PDO::ATTR_EMULATE_PREPARES = false` – çaktivizon simulimin e deklaratave të përgatitura.

Në rast të dështimit të lidhjes, gabimi regjistrohet në log përmes `error_log()`, ndërsa aplikacioni kthen kodin HTTP `500` dhe shfaq një mesazh informues për përdoruesin.

Lidhja e krijuar ruhet në variablën `$pdo` dhe përdoret nga pjesët e tjera të Backend-it për komunikimin me databazën.

## 2.2 Organizimi i Backend-it

Backend-i i sistemit është organizuar në shtresa të ndara për të mundësuar ndarjen e përgjegjësive dhe mirëmbajtjen më të lehtë të kodit.

Një nga komponentët kryesorë është `ClientController.php`, i cili komunikon me `ClientService.php` dhe menaxhon kërkesat që lidhen me klientët.

Controller-i përmban funksionet kryesore për:

- Shfaqjen e listës së klientëve dhe statistikave.
- Kërkimin e klientëve.
- Shfaqjen e profilit të klientit sipas identifikuesit.
- Regjistrimin e klientëve të rinj.
- Përditësimin e të dhënave të klientëve.
- Fshirjen e klientëve.

Përpunimi i logjikës së këtyre operacioneve delegohet te shtresa Service, ndërsa Controller-i merr dhe kthen të dhënat përkatëse.

## 2.3 Logjika e Backend-it

Logjika e aplikacionit për klientët realizohet përmes klasës `ClientService.php`, e cila komunikon me `ClientRepository.php` për të kryer operacionet me databazën.

Shërbimi për klientët përmban funksionalitete për kërkimin, shfaqjen, regjistrimin, përditësimin dhe fshirjen e klientëve.

Gjithashtu, sistemi mundëson kategorizimin e klientëve sipas numrit të vizitave të përfunduara:

- Klient i ri – pa vizita të përfunduara.
- Klient aktiv – me të paktën një vizitë të përfunduar.
- Klient besnik – me pesë ose më shumë vizita të përfunduara.

Para ruajtjes së të dhënave, sistemi kontrollon vlefshmërinë e tyre. Në rast të gabimeve gjatë komunikimit me databazën, ato regjistrohen në log dhe përdoruesit i kthehet një mesazh informues.

Metodat e klasës `ClientService.php` kontrollojnë vlefshmërinë e identifikuesve, përpunojnë të dhënat e klientëve dhe komunikojnë me Repository për realizimin e operacioneve. Gabimet gjatë regjistrimit, përditësimit dhe fshirjes trajtohen përmes `PDOException`.

## 2.4 Validimi dhe trajtimi i gabimeve

Validimi i të dhënave realizohet në shtresën `ClientService.php`, përmes metodës `validateClientData()`.

Kontrollet kryesore përfshijnë:

- Emri dhe mbiemri janë fusha të detyrueshme.
- Emri dhe mbiemri nuk duhet të kalojnë 100 karaktere.
- Numri i telefonit nuk duhet të kalojë 30 karaktere.
- Email-i kontrollohet për format të vlefshëm.
- Email-i nuk duhet të kalojë 150 karaktere.
- Adresa nuk duhet të kalojë 255 karaktere.

Të dhënat pastrohen me `trim()` dhe fushat opsionale bosh ruhen si `NULL`.

Në rast se të dhënat nuk janë të vlefshme, operacioni ndërpritet dhe kthehet lista e gabimeve. Gjithashtu, gabimet gjatë operacioneve me databazën trajtohen përmes `PDOException` dhe regjistrohen me `error_log()`.

## 2.5 Komunikimi me databazën

Komunikimi ndërmjet Backend-it dhe databazës realizohet përmes klasës `ClientRepository.php`, e cila përdor objektin PDO për ekzekutimin e komandave SQL.

Kjo shtresë përmban operacionet kryesore për menaxhimin e të dhënave të klientëve:

- `SELECT` – marrja e listës së klientëve, profileve dhe statistikave.
- `INSERT` – regjistrimi i klientëve të rinj.
- `UPDATE` – ndryshimi i të dhënave ekzistuese.
- `DELETE` – fshirja e klientëve.

Përveç këtyre operacioneve, përdoren `LEFT JOIN` dhe `INNER JOIN` për të marrë informacione të lidhura me terminet dhe pagesat e klientëve.

Për ekzekutimin e kërkesave përdoren metodat `prepare()` dhe `execute()`, të cilat mundësojnë kalimin e parametrave në deklaratat SQL dhe ndihmojnë në mbrojtjen nga SQL Injection.

Kjo ndarje e përgjegjësive mundëson që logjika e aplikacionit të qëndrojë në shtresën Service, ndërsa komunikimi me databazën të menaxhohet nga Repository.

# 3. API – NDËRFAQJA E KOMUNIKIMIT

## 3.1 Përshkrimi i API-së

Sistemi LUMIÈRE Beauty Studio përmban një API të zhvilluar në PHP, e cila mundëson komunikimin me databazën përmes kërkesave HTTP dhe shkëmbimin e të dhënave në formatin JSON.

API-ja për menaxhimin e klientëve është realizuar në skedarin `api/clients.php` dhe mundëson kryerjen e operacioneve CRUD: shfaqjen, regjistrimin, përditësimin dhe fshirjen e klientëve.

## 3.2 Endpoint-et dhe metodat HTTP

Endpoint-i kryesor:

`api/clients.php`

| Metoda | Parametrat | Përshkrimi | Kodi HTTP |
|---|---|---|---|
| GET | Pa parametra | Shfaq të gjithë klientët | 200 |
| GET | `id` | Shfaq një klient sipas ID-së | 200 / 404 |
| POST | Të dhëna JSON | Regjistron klient të ri | 201 |
| PATCH | `id` dhe të dhëna JSON | Përditëson klientin | 200 |
| DELETE | `id` | Fshin klientin | 200 / 404 |

Për shembull, për regjistrimin e një klienti dërgohen të dhënat në formatin JSON:

```json
{
  "emri": "Arta",
  "mbiemri": "Krasniqi",
  "telefoni": "044123456",
  "email": "arta@example.com",
  "adresa": "Prizren"
}
```



## 3.3 Siguria dhe validimi i kërkesave

API-ja përmban disa mekanizma për sigurinë dhe kontrollin e të dhënave:

- Kontrollon nëse përdoruesi është i kyçur si administrator.
- Përdor token CSRF për kërkesat POST, PATCH dhe DELETE.
- Kontrollon formatin JSON të të dhënave të pranuara.
- Verifikon fushat e lejuara dhe fushat e detyrueshme.
- Kontrollon gjatësinë e të dhënave dhe formatin e email-it.
- Përdor deklarata SQL të përgatitura për komunikim me databazën.
- Kthen përgjigje JSON me kode përkatëse HTTP në rast suksesi ose gabimi.

## 3.4 Përgjigjet dhe trajtimi i gabimeve

API-ja kthen përgjigje në formatin JSON, të cilat përmbajnë informacion për suksesin ose gabimin e kërkesës.

Kodet kryesore HTTP të përdorura janë:

| Kodi HTTP | Përshkrimi |
|---|---|
| 200 | Kërkesa u realizua me sukses. |
| 201 | Klienti u regjistrua me sukses. |
| 400 | Kërkesa ose të dhënat nuk janë në format të saktë. |
| 401 | Përdoruesi nuk është i autorizuar. |
| 403 | Token-i CSRF nuk është valid. |
| 404 | Klienti nuk u gjet. |
| 405 | Metoda HTTP nuk lejohet. |
| 409 | Ekziston konflikt me të dhënat e lidhura. |
| 422 | Të dhënat nuk i plotësojnë kushtet e validimit. |
| 500 | Gabim i brendshëm në server ose në databazë. |

Përgjigjet JSON përmbajnë fusha si `success`, `message` dhe `error`, në varësi të rezultatit të kërkesës.

## 3.5 Testimi i API-së

API-ja është testuar përmes Postman, duke përdorur endpoint-in `api/clients.php`.

| Metoda HTTP | Testimi | Rezultati |
|---|---|---|
| GET | Shfaqja e klientëve | Lista e 7 klientëve u kthye me sukses. |
| POST | Regjistrimi i klientit | Klienti u regjistrua me sukses (ID: 9). |
| PATCH | Përditësimi i klientit | Të dhënat u përditësuan me sukses. |
| DELETE | Fshirja e klientit | Klienti u fshi me sukses. |
| POST | Email i pavlefshëm | U kthye gabimi `INVALID_EMAIL`. |
| POST | Mungesa e emrit | U kthye gabimi `REQUIRED_FIELDS`. |
| GET | ID e klientit që nuk ekziston | U kthye gabimi `CLIENT_NOT_FOUND`. |
| POST | Token CSRF i pavlefshëm | U kthye gabimi `INVALID_CSRF_TOKEN`. |
| PUT | Metodë HTTP e palejuar | U kthye gabimi `METHOD_NOT_ALLOWED`. |

Testimet konfirmuan funksionimin e operacioneve CRUD, validimin e të dhënave dhe mekanizmat bazë të sigurisë së API-së. Gjatë testimit u verifikua edhe mekanizmi CSRF. Kërkesa me token të pavlefshëm u refuzua nga API-ja.

## 3.6 Problemet teknike dhe zgjidhjet

Gjatë zhvillimit dhe testimit të sistemit janë hasur disa probleme teknike, të cilat janë zgjidhur gjatë procesit të implementimit.

| Problemi | Zgjidhja |
|---|---|
| Gabimi gjatë lidhjes me databazën | U kontrollua konfigurimi i PDO-së dhe lidhja me databazën MySQL. |
| Gabimi `UNAUTHORIZED` gjatë testimit në Postman | U vendos Cookie i sesionit PHP në kërkesat e Postman. |
| Gabimi `INVALID_CSRF_TOKEN` | U rifreskua token-i CSRF dhe u vendos në header-in përkatës. |
| Gabimet gjatë validimit të të dhënave | U shtuan kontrollet për fushat e detyrueshme dhe formatin e email-it. |
| Kërkesat me metoda HTTP të palejuara | U implementua kontrolli i metodave të mbështetura nga API-ja. |

Këto zgjidhje ndihmuan në përmirësimin e funksionimit, sigurisë dhe besueshmërisë së sistemit.


## 4. Përfundimi

Gjatë realizimit të Raportit Teknik 2, projekti kaloi nga faza e dizajnimit në implementimin praktik të databazës dhe backend-it. U realizua lidhja me databazën MySQL përmes PDO-së, u zhvillua logjika për menaxhimin e klientëve dhe u implementua API-ja për operacionet CRUD.
Gjithashtu, u implementuan mekanizmat e validimit, autentikimit dhe sigurisë, ndërsa funksionimi i API-së u verifikua përmes testimeve në Postman.
Në këtë fazë, projekti ka një bazë funksionale për menaxhimin e të dhënave të sallonit dhe mundësi për zhvillim të mëtejshëm të moduleve të tjera.






