
# RAPORTI TEKNIK 1
## Sistemi për Menaxhimin e Salloneve të Bukurisë – LUMIÈRE Beauty Studio

Projekti synon zhvillimin e një sistemi softuerik për menaxhimin e klientëve, termineve, shërbimeve dhe stafit të salloneve të bukurisë. Qëllimi është të lehtësohet organizimi i punës, të zvogëlohen gabimet në rezervimin e termineve dhe të mundësohet menaxhimi më efikas i sallonit.

---

## 1. Problemi dhe qëllimi i projektit

### 1.1 Problemi që zgjidh projekti

Sallonet e bukurisë kanë nevojë për një mënyrë të organizuar për menaxhimin e klientëve, shërbimeve, punonjësve dhe termineve. Menaxhimi manual i këtyre të dhënave mund të shkaktojë gabime në rezervime, mbivendosje të termineve dhe vështirësi në përcjelljen e shërbimeve të kryera.

Për këtë arsye, projekti synon krijimin e një sistemi që i centralizon këto të dhëna dhe e lehtëson organizimin e përditshëm të sallonit.

### 1.2 Përdoruesit e sistemit

Përdoruesit kryesorë të sistemit janë administratori dhe punonjësit e sallonit të bukurisë.

Administratori menaxhon klientët, shërbimet, çmimet, stafin dhe rezervimet, ndërsa punonjësit mund të përdorin sistemin për të parë terminet e caktuara dhe shërbimet që duhet të kryejnë, në varësi të privilegjeve të tyre të qasjes.

---

## 2. Kërkesat e projektit

Kërkesat e sistemit për menaxhimin e salloneve të bukurisë ndahen në kërkesa funksionale dhe jofunksionale. Kërkesat funksionale përshkruajnë veprimet që sistemi duhet t'i mundësojë, ndërsa kërkesat jofunksionale përcaktojnë kushtet e sigurisë, përdorshmërisë dhe cilësisë së sistemit.

### 2.1 Kërkesat funksionale

- Regjistrimi dhe menaxhimi i të dhënave të klientëve.
- Shtimi, ndryshimi dhe fshirja e shërbimeve të sallonit.
- Regjistrimi i çmimeve dhe kohëzgjatjes së shërbimeve.
- Rezervimi i termineve për shërbime të ndryshme.
- Shfaqja e termineve të lira dhe të rezervuara.
- Ndryshimi i datës dhe orës së një termini ekzistues.
- Anulimi i rezervimeve dhe ndryshimi i statusit të terminit.
- Menaxhimi i të dhënave dhe orarit të punonjësve.
- Shfaqja e listës së termineve ditore dhe javore.
- Kërkimi i klientëve sipas emrit ose numrit të telefonit.
- Shikimi i historikut të termineve të klientëve.

### 2.2 Kërkesat jofunksionale

- Fjalëkalimet ruhen në formë të hash-uar.
- Funksionet administrative mbrohen nga qasja e paautorizuar.
- Të dhënat personale të klientëve mbrohen nga qasja e paautorizuar.
- Ndërfaqja duhet të jetë e thjeshtë dhe e lehtë për përdorim.
- Sistemi duhet të jetë i përdorshëm në kompjuter, tablet dhe telefon.
- Të dhënat duhet të ruhen në mënyrë të strukturuar në databazë.
- Sistemi duhet të ruajë saktë të dhënat dhe të shmangë rezervimet që mbivendosen për të njëjtin punonjës.
- Sistemi duhet t'i shfaqë listat e termineve brenda 3 sekondave për ngarkesën e përcaktuar të testimit.
- Fushat e detyrueshme duhet të kontrollohen para se formulari të dërgohet për ruajtje.
- Në rast të gabimit gjatë ruajtjes, sistemi duhet të shfaqë mesazh përkatës dhe të mos konfirmojë një rezervim që nuk është ruajtur me sukses.
- Databaza duhet të mund të kopjohet dhe të rikthehet nga një kopje rezervë.

---

## 3. Teknologjitë e zgjedhura dhe arkitektura e sistemit

### 3.1 Frontend – HTML, CSS dhe JavaScript

HTML, CSS dhe JavaScript përdoren për krijimin e ndërfaqes së përdoruesit, formularëve për regjistrimin e klientëve, shfaqjen e termineve dhe paraqitjen vizuale të sistemit. Këto teknologji janë zgjedhur për të krijuar një ndërfaqe të qartë dhe të lehtë për përdorim.

### 3.2 Backend – PHP

PHP përdoret për përpunimin e kërkesave të sistemit, validimin e të dhënave, menaxhimin e rezervimeve dhe komunikimin me databazën. Është zgjedhur për të realizuar logjikën e aplikacionit dhe funksionet kryesore të tij.

### 3.3 Databaza – MySQL

MySQL përdoret për ruajtjen dhe organizimin e të dhënave për klientët, punonjësit, shërbimet dhe terminet. Është zgjedhur për organizimin e të dhënave në tabela dhe krijimin e relacioneve ndërmjet tyre.

### 3.4 Menaxhimi i versioneve – Git dhe GitHub

Git përdoret për përcjelljen e ndryshimeve në kod, ndërsa GitHub për ruajtjen e repository-t dhe dokumentimin e progresit të projektit gjatë semestrit.

### 3.5 Arkitektura e sistemit

Sistemi përbëhet nga tri shtresa kryesore:

- **Frontend:** HTML, CSS dhe JavaScript.
- **Backend:** PHP.
- **Databaza:** MySQL.

Frontend-i shërben për ndërfaqen e përdoruesit, Backend-i për përpunimin e kërkesave, ndërsa MySQL për ruajtjen e të dhënave. Komunikimi ndërmjet tyre mundëson funksionimin e sistemit.

---

## 4. Modeli fillestar i databazës

Databaza e sistemit për menaxhimin e salloneve të bukurisë përdoret për ruajtjen dhe organizimin e të dhënave për klientët, stafin, shërbimet dhe terminet e rezervuara.

Struktura fillestare përbëhet nga katër tabela kryesore:

- **Klientet**
- **Stafi**
- **Sherbimet**
- **Terminet**

Çdo tabelë ka një çelës primar (PK), ndërsa tabela `Terminet` përmban çelësa të huaj (FK) që mundësojnë lidhjen me tabelat e tjera.

Relacionet janë të tipit një-me-shumë (1:N), pasi një klient, punonjës ose shërbim mund të lidhet me shumë termine. Ky model mundëson organizimin e saktë të të dhënave dhe menaxhimin më të lehtë të rezervimeve.

---

## 5. Repository i projektit

Kodi burimor dhe dokumentacioni i projektit ruhen në GitHub:

[ Sistemi për Menaxhimin e Salloneve të Bukurisë – GitHub ](https://github.com/sarabesimi20-prog/SistemiMenaxhimitSalloneveTeBukurise)

---

## 6. Plani i punës

Deri te Raporti Teknik 2, planifikohet të realizohen këto aktivitete:

- Krijimi i databazës në MySQL dhe i tabelave kryesore për klientët, stafin, shërbimet dhe terminet.
- Zhvillimi i backend-it me PHP për përpunimin e kërkesave të sistemit.
- Implementimi i funksioneve kryesore për regjistrimin dhe menaxhimin e klientëve, shërbimeve dhe termineve.
- Krijimi i API-ve kryesore për komunikimin ndërmjet frontend-it dhe backend-it.
- Testimi i funksioneve të implementuara me të dhëna testuese.
- Dokumentimi i progresit dhe ruajtja e ndryshimeve në GitHub.
