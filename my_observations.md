# Brak obsługi eventu `goal`

Brakuje zapisu do statystyk eventu `goal`. Jest tylko if na `faul`. W testach widzę, że mamy event `goal`, ale nie posiada
pełnych danych zgodnych z README.md

# Złamanie zasad SOLID

## Single Responsibility Principle  

W StatisticManager jest kilka odpowiedzialności. Operuje on bezpośrednio na pliku zamiast wykorzystac FileStorage (docelowo repository pattern).
Czyli mamy logikę biznesowa (aktualizacja statystyk) wraz z operacjami I\O (warstwa infrastruktury)

## Open/Closed Principle

EventHandler łamię ta zasade. Mamy tam `if ($data['type'] === 'foul')` i jeśli chcemy wprowadzać tam nowy typ eventu, musimy zmienić kod w tej klasie. 
Lepszym rozwiazaniem jest wprowadzenie wzorca strategorii i dobieranie strategii na podstawie typu eventu. 
Dzięki temu podejściu kod jest otwarty na modyfikacje i zamkniety na zmiany - dodajemy nowe strategie bez modyfikacji EventHandler.

## Liskov Substitution Principle

W tym przypadku mamy brak segregacji interfejsów. Używamy bezpośredniej implementacji FileStorage w StatisticsManager i EventHandler.
Po wprowadzeniu interface np StorageInterface, możemy zastosować Dependency Inversion Principle i wprowadzić kilka implementacji StorageInterface.
Przykładowo: FileStorage, DatabaseStorage, RedisStorage itp. Czyli w ramach PoC mamy FileStorage i gdy pomysł wypali możemy bez
problemu wprowadzić np. DatabaseStorage bo posługujemy się kontraktem StorageInterface.

## Interface Segregation Principle

FileStorage ma metody save() i getAll(), ale nie sa one zawsze potrzebne. 
EventHandler używa tylko save(). StatisticManager ma zaleznosc do FileStorage, ale operuje bezposrednio na pliku. 
W README.md była informacja o potrzebie dużej wydajności. Możemy więc docelowo rozdzielić na zapis (primary) i odczyt (replica).
Czyli mamy wtedy dwa interfesy np StorageInterface oraz ReadInterface. Na potrzeby PoC wystarczy nam pewnie wprowadzenie repository pattern.

## Dependency Inversion Principle

Klasy EventHandler i StatisticManager maja zaleznosc do konkretnej implementacji (FileStorage) zamiast do kontraktu - interfejsu.
Tworzymy też FileStorage poprzez `new` w kontruktorach. Musimy pozbyć się tworzenai FileStorage w konstruktorze i przekazywać go jako argument.
Pomoże nam to też w testach bo możemy np utworzyć InMemeryStorage i nie potrzebujemy mieć zaleznosci w unit testach do operacji I/O. 


# Inbox/Outbox pattern

Istnieje ryzyko podwójnego przetworzenia tego samego eventu co może wprowadzać w bład naszych klientów. Co jeśli przetworzymy 2x event
o strzeleniu gola lub otrzymaniu 2x żółtej kartki przez tego samego gracza? 
Wprowadzamy też inne problemy, dodatkowe workery, monitoring, eventual consistency - PoC nie jest tego wart. 

# Brak transakcji

To jest PoC, ale docelowo, aby aplikacja byla spójna trzeba wprowadzić transkcje, gdy będziemy korzystać z bazy danych. 
Jak aktualizować statystyki? Wprowadzić optimistic czy pesimistic locking? Czy w ramach PoC potrzebujemy tego?
Czy problem z race conditions nas boli w ramach PoC?

# Inne przemyślenia

Czy to jest idealny przypadek do event sourcingu? Czy PoC to dobry moment na implementacje tego patternu? 
W README.md jest informacja o real-time aktualizacjach dla klientów. Czy warto inwestować teraz w WebSocket lub Server Sent Events? 
Klasyczny polling powinien być wystarczajacy na potrzeby PoC. 

Wprowadzic VO dla matchId, teamId - obecnie to sa stringi i mozna latwo pomyluc kolejnosc argumentow. 

# Architektura rozwiazania

Czy w ramach PoC stosować hexagonal architecture? Czy może wystarczy jak dodam tylko katalog Infrastructure i tam umieszcze implementacje
FileStorage? Jest sens dodawać Application i Domain? Nie mam modelu danych pod statystyki - trzeba dodać?

# Code quality

Oczywiscie w ramach CI mozna dodac jeszcze phpstan czy deptract, ale w ramach PoC odpuszczam bo mamy ograniczony czas, dodałem tylko php-cs-fixer wraz z podstawowym CI.

# Architecture

Cechy architektury docelowej jakie zauważyłem na podstawei README:
- skalowalność - musimy obsługiwać duża liczbe eventow
- real-time - tutaj sa sprzeczne informacje, trzeba dopytac biznesu czy 5s opoznienia to dalej jest real-time dla nich?
- spójnosc danych - musimy miec pewnosc, że nie przetworzymy 2x tego samego eventu 

Czy możemy iść w event-driven? Czy opóznienia zwiazane z asynchronicznościa sa krytyczne dla biznesu? 
Co z eventual consistency - przy EDA jest to naturalne?  
Czy docelowo chcemy implementowac Inbox/Outbox pattern? Rozwiazuje to problemy z podwójnym przetworzeniem eventu lub zapewnia w
jakimś stopniu gwarancje wysłania wiadomosci, ale wprowadza nam nowe problemy. Czy problemem bedzie waskie gardlo
cron joba, który by publikowac eventy z outboxa? Czy mozemy tam zastosowac `select ... for update`? 

# Target solution & Questions

Bazujac na doświadczeniu z przetwarzania statystyk (małe doświadczenie, ale jakieś jest :), branża edukacyjna) podszedłbym do tego tak:
- zapisujmy wszystkie eventy w postaci ogolnej, czyli wszystkie dane zapisuje docelowo pewnie w bazie danych
- po zapisisaniu emitujemy event, który informuje, że "coś" się stało podczas rozgrywek
- na event nasłuchuja inne usługi, które specjalizuja sie w tworzeniu szczegółowych statystyk 
- po zapisaniu szczeglowej statystyki tez jest emitowany event i moze to byc przechwycone przez inne usługi i wyslane za pomoca websocet/sse lub po prostu frontend robi polling po swieze dane

Pisałem tutaj o usługach, ale równie dobrze moze to być modularny monolit. 
Zamiast komunikacji asynchronicznej poprzez message bus mozemy zastosowac facade pattern i moduly komunikja sie za pomoca wzorca fasady.

W moim rozwiazaniu nie zastosowalem wzorca strategii w EventHandler bo uznalem to za over-engineering na potrzeby PoC.
Przeniosłem za to logikę obsłgujaca zapis do StatisticsManager.

Został jeszcze do rozwiazania problem walidacji. Docelowo pewnie dane byłyby walidowane w kontrolerze (walidator) - czy np mamy teamId.
Czy walidacja typu eventu to jest logika biznesowa czy to tylko ograniczenie ze wzgledu na PoC?

Wdrożenie modelu danych dla statystyk. Czy rozne typy statystyk maja rozna strukture danych? 

`All clients receive event notifications` - jacy klienci? To sa aplikacje frontend? Uzywaja pollingu/websockers/sse? Czy klienci to inne microservices?
A może tutaj trzeba zaimplementować obsługę wysyłania webhooks do wszystkich naszych klientow? - outbox pattern

Brakuje mi implementacji dla `All clients receive event notifications` - tutaj nie wiem dokładnie o jakich klientów chodzi,
ale jeśli sa to:
- aplikacje frontend - tutaj moga robić polling, możemy użyć websockets lub sse. Do obsługi websocket lub sse lepiej sprawdzi się usługa napisana w JS/TS, możemy jej dostarczyc dane za pomoca kolejki i eventu
- inne microservices - publikujemy event na kolejce i zainteresowane usługi ja dostana
- aplikacje klientów - wysylamy im webhooks - mozemy zastosowac outbox pattern i przy zapisie eventu do bazy dodajemy rownież wpis do tabelki z outbox i pózniej worker obsłguje to i pewnie potrzebuje odczytać konfiguracje dla webhooks i wysłać do odpowiednich aplikacji lub jeszcze raz dodac wpisy do outbox, ale juz jeden wpis dla jednej aplikacji

Co jest wazne przy powiadomieniu aplikacji klientow? W evencie musimy dostarczyć dane z momentu otrzymania requestu z eventem. Czyli nie możemy im wysyłac np matchId i oni maja pobrac sobie dane
bo te dane moga byc juz inne - w między czasie zosały zaktualizowane. 
