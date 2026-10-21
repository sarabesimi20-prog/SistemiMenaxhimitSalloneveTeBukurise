# Tema : Sistemi per Menaxhimin e Salloneve te Bukurise



## Raporti Teknik 1 : 



Projekti synon zhvillimin e një sistemi softuerik për menaxhimin e klientëve, termineve, shërbimeve dhe stafit të salloneve të bukurisë. Qëllimi është të lehtësohet organizimi i punës, të zvogëlohen gabimet në rezervimin e termineve dhe të mundësohet menaxhimi më efikas i sallonit . 



## Kerkesat e projektit :

Kerkesat per kete sistem jane:

Kërkesat funksionale	


Regjistrimi dhe menaxhimi i të dhënave të klientëve.



Shtimi, ndryshimi dhe fshirja e shërbimeve të sallonit.



Regjistrimi i çmimeve dhe kohëzgjatjes së secilit shërbim.



Rezervimi i termineve për shërbime të ndryshme.



Shfaqja e termineve të lira dhe të rezervuara.



Ndryshimi i datës dhe orës së një termini ekzistues.



Anulimi i rezervimeve nga administratori.



Parandalimi i rezervimeve që mbivendosen për të njëjtin punonjës.



Menaxhimi i të dhënave të punonjësve të sallonit.



Caktimi i punonjësve për shërbime të caktuara.



Organizimi i orarit të punës së punonjësve.



Shfaqja e listës së termineve ditore dhe javore.



Kërkimi i klientëve sipas emrit ose numrit të telefonit.



Shikimi i historikut të termineve të klientëve.



Përditësimi i të dhënave personale të klientëve.



Menaxhimi i statusit të terminit, si i rezervuar, i përfunduar ose i anuluar.



Regjistrimi dhe përcjellja e pagesave për shërbimet e kryera.



Llogaritja e kostos totale të shërbimeve të rezervuara.



Gjenerimi i raporteve për terminet dhe shërbimet e kryera.



Autentikimi i administratorit përmes emrit të përdoruesit dhe fjalëkalimit.



Kontrollimi i të dhënave të formularëve para ruajtjes në databazë.



Shfaqja e mesazheve të kuptueshme kur të dhënat e futura janë të pavlefshme.








Kerkesat jofunksionale








Fjalëkalimet duhet të ruhen në formë të hash-uar dhe jo si tekst i zakonshëm.



Sistemi duhet të mbrojë të dhënat personale të klientëve nga qasja e paautorizuar.



Funksionet administrative duhet të jenë të qasshme vetëm për përdoruesit e autorizuar.



Ndërfaqja duhet të jetë e thjeshtë dhe e lehtë për përdorim.



Sistemi duhet të jetë i përdorshëm në kompjuter, tablet dhe telefon.



Sistemi duhet të funksionojë në versionet e fundit të shfletuesve Chrome dhe Edge.



Listat e termineve duhet të shfaqen brenda 3 sekondave në kushtet e përcaktuara të testimit.



Të dhënat duhet të ruhen në mënyrë të strukturuar dhe të sigurt në databazë.



Kodi duhet të organizohet në mënyrë që mirëmbajtja e sistemit të jetë më e lehtë.



Sistemi duhet të shfaqë mesazhe të kuptueshme kur ndodhin gabime.



Komunikimi ndërmjet shfletuesit dhe serverit duhet të përdorë HTTPS në ambientin e publikimit.







## Tekonologjite/arkitektura

- frontend



HTML, CSS dhe JavaScript përdoren për krijimin e ndërfaqes së përdoruesit, formularëve për regjistrimin e klientëve, shfaqjen e termineve dhe paraqitjen vizuale të sistemit. Këto teknologji janë zgjedhur për të krijuar një ndërfaqe të qartë dhe të lehtë për përdorim.




- backend


 PHP përdoret për përpunimin e kërkesave të sistemit, validimin e të dhënave, menaxhimin e rezervimeve dhe komunikimin me databazën. Është zgjedhur për të realizuar logjikën e aplikacionit dhe funksionet kryesore të tij.



- database 


	MySQL përdoret për ruajtjen dhe organizimin e të dhënave për klientët, punonjësit, shërbimet dhe terminet. Është zgjedhur për organizimin e të dhënave në tabela dhe krijimin e relacioneve ndërmjet tyre.



- Git dhe GitHub 


Git përdoret për përcjelljen e ndryshimeve në kod, ndërsa GitHub për ruajtjen e repository-t dhe dokumentimin e progresit të projektit gjatë semestrit.



##	Modeli fillestar i databazës


Databaza e sistemit për menaxhimin e salloneve të bukurisë përdoret për ruajtjen dhe organizimin e të dhënave për klientët, stafin, shërbimet dhe terminet e rezervuara.Struktura fillestare përbëhet nga katër tabela kryesore: Klientet, Stafi, Sherbimet dhe Terminet. Çdo tabelë ka një çelës primar (PK), ndërsa tabela Terminet përmban çelësa të huaj (FK) që mundësojnë lidhjen me tabelat e tjera.Relacionet janë të tipit një-me-shumë (1), pasi një klient, punonjës ose shërbim mund të lidhet me shumë termine. Ky model mundëson organizimin e saktë të të dhënave dhe menaxhimin më të lehtë të rezervimeve.













