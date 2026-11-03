# RAPORTI TEKNIK 2 – DATABAZA, BACKEND-I DHE API



**Projekti:** Sistem menaxhimi te salloneve te bukurise 



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

Lidhjet kryesore të deklaruara janë:

- Klienti → terminet dhe shënimet e klientit.
- Stafi → terminet, orari i punës dhe mungesat.
- Shërbimi → terminet.
- Termini → pagesat dhe vlerësimet.
- Produkti → lëvizjet e stokut.
- Përdoruesi → aktivitetet dhe njoftimet.
- Përdoruesi → anëtari i stafit (lidhje opsionale)

Skema përdor çelësa primarë (`PRIMARY KEY`) për identifikimin unik të regjistrimeve dhe çelësa të huaj (`FOREIGN KEY`) për ruajtjen e lidhjeve ndërmjet tabelave. Kufizimet `UNIQUE` përdoren për email-in e përdoruesve dhe administratorëve, kodin e kuponit, lidhjen e shënimeve me klientin dhe kombinimin e stafit me ditën e javës në orarin e punës.

Gjithashtu përdoren fusha `NOT NULL`, vlera të parazgjedhura (`DEFAULT`) dhe fusha `ENUM` për kufizimin e statuseve dhe llojeve të caktuara.

Tabelat përdorin motorin InnoDB, i cili mundëson ruajtjen e integritetit referencial përmes lidhjeve ndërmjet të dhënave.

