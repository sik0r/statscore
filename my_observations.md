# Problemy w obecnym kodzie

## Brak obsługi eventu `goal`

Brakuje zapisu do statystyk eventu `goal`. Jest tylko if na `foul`. W testach widzę, że mamy event `goal`, ale nie posiada pełnych danych zgodnych z README.md.

## Złamanie zasad SOLID

### Single Responsibility Principle  

W StatisticManager jest kilka odpowiedzialności. Operuje on bezpośrednio na pliku zamiast wykorzystac FileStorage (docelowo repository pattern).
Czyli mamy logikę biznesowa (aktualizacja statystyk) wraz z operacjami I\O (warstwa infrastruktury)

### Open/Closed Principle

EventHandler łamie tę zasadę. Mamy tam if ($data['type'] === 'foul') i jeśli chcemy wprowadzać nowy typ eventu, 
musimy zmienić kod w tej klasie. Lepszym rozwiązaniem jest wprowadzenie wzorca strategii i dobieranie strategii na podstawie typu eventu.
Dzięki temu kod jest otwarty na modyfikacje i zamknięty na zmiany - dodajemy nowe strategie bez modyfikacji EventHandler.

### Liskov Substitution Principle

Brak abstrakcji (interfejsów) uniemożliwia zastosowanie LSP. FileStorage używane w różnych kontekstach bez wspólnego kontraktu.
Przy wprowadzeniu interfejsów, każda implementacja (FileStorage, DatabaseStorage, RedisStorage) może być zamiennie używalna bez wpływu na klientów.

### Interface Segregation Principle

FileStorage ma metody save() i getAll(), ale nie są one zawsze potrzebne. EventHandler używa tylko save(). 
StatisticsManager ma zależność do FileStorage, ale operuje bezpośrednio na pliku. W README.md była informacja o potrzebie dużej wydajności.
Możemy więc docelowo rozdzielić na zapis (primary) i odczyt (replica). Czyli mamy wtedy dwa interfejsy, np. StorageInterface oraz ReadInterface. 
Na potrzeby PoC wystarczy nam pewnie wprowadzenie repository pattern.

### Dependency Inversion Principle

Klasy EventHandler i StatisticsManager mają zależność do konkretnej implementacji (FileStorage) zamiast do kontraktu - interfejsu.
Tworzymy też FileStorage poprzez new w konstruktorach. Musimy pozbyć się tworzenia FileStorage w konstruktorze i przekazywać go jako argument.
Pomoże nam to też w testach, bo możemy np. utworzyć InMemoryStorage i nie potrzebujemy mieć zależności w unit testach do operacji I/O.

## Inne problemy techniczne

### Brak transakcji

To jest PoC, ale docelowo, aby aplikacja była spójna, trzeba wprowadzić transakcje, gdy będziemy korzystać z bazy danych.
Jak aktualizować statystyki? Wprowadzić optimistic czy pessimistic locking? Czy w ramach PoC potrzebujemy tego? 
Czy problem z race conditions nas boli w ramach PoC - mamy locki na plik obecnie?

### Brak Value Objects

Wprowadzić VO dla matchId, teamId, playerId - obecnie to są stringi i można łatwo pomylić kolejność argumentów.

### Brak modelu danych

Nie mam modelu danych pod statystyki - trzeba dodać? Goal i foul maja rozne modele danych. Czy dojdzie nam wiecej eventow z roznym modelem danych?

# Architektura rozwiązania

## Rozważania o architekturze

Czy w ramach PoC stosować hexagonal architecture? Czy może wystarczy, jak dodam tylko katalog Infrastructure i tam umieszczę 
implementację FileStorage? Jest sens dodawać Application i Domain?

W moim rozwiązaniu nie zastosowałem wzorca strategii w EventHandler, bo uznałem to za over-engineering na potrzeby PoC.
Przeniosłem za to logikę obsługującą zapis do StatisticsManager.

## Cechy architektury docelowej

Na podstawie README zauważyłem następujące wymagania:
- **skalowalność** - system musi obsługiwać dużą liczbę eventów (high volume)
- **real-time delivery** - klienci otrzymują powiadomienia w czasie rzeczywistym (pytanie: czy 5s opóźnienia to akceptowalne real-time?)
- **integralność danych** - data integrity at all times (np. brak podwójnego przetworzenia tego samego eventu)
- **trwałość danych** - permanentne przechowywanie wszystkich eventów i możliwość ich odzyskania (historical data preserved and accessible)
- **niezawodność** - reliable and consistent communication (gwarancja dostarczenia notyfikacji do klientów)
- **dokładność** - accurate statistics calculation (prawidłowe obliczanie statystyk dla goal i foul)
- **kompletność** - obsługa wszystkich wymaganych typów eventów z pełnymi danymi

Czy możemy iść w event-driven? Czy opóźnienia związane z asynchronicznością są krytyczne dla biznesu?
Co z eventual consistency - przy EDA jest to naturalne?

## Rozważania o patterns

### Event sourcing

Czy to jest idealny przypadek do event sourcingu? Czy PoC to dobry moment na implementację tego patternu?
Tak ważna decyzja architektoniczna nie powinna być podejmowana na tak wczesnym etapie - przepalamy czas zamiast przetestowac pomysł.

### Inbox/Outbox pattern

Istnieje ryzyko podwójnego przetworzenia tego samego eventu, co może wprowadzać w błąd naszych klientów.
Co jeśli przetworzymy 2x event o strzeleniu gola lub otrzymaniu 2x żółtej kartki przez tego samego gracza?

Czy docelowo chcemy implementować Inbox/Outbox pattern? Rozwiązuje to problemy z podwójnym przetworzeniem eventu lub
zapewnia w jakimś stopniu gwarancje wysłania wiadomości, ale wprowadza nowe problemy. 
Czy problemem będzie wąskie gardło cron joba, który by publikował eventy z outboxa? Czy możemy tam zastosować `select ... for update`?

Wprowadzamy też inne problemy: dodatkowe workery, monitoring, eventual consistency - PoC nie jest tego wart.

### WebSocket / Server Sent Events

W README.md jest informacja o real-time aktualizacjach dla klientów. Czy warto inwestować teraz w WebSocket lub Server Sent Events? 
Klasyczny polling powinien być wystarczający na potrzeby PoC.

# Propozycja rozwiązania docelowego

Bazując na doświadczeniu z przetwarzania statystyk (małe doświadczenie, ale jakieś jest :), branża edukacyjna) podszedłbym do tego tak:
- zapisujmy wszystkie eventy w postaci ogólnej, wszystkie dane zapisujemy docelowo pewnie w bazie danych
- po zapisaniu emitujemy event, który informuje, że "coś" się stało podczas rozgrywek
- na event nasłuchują inne usługi, które specjalizują się w tworzeniu szczegółowych statystyk
- po zapisaniu szczegółowej statystyki też jest emitowany event i może to być przechwycone przez inne usługi i wysłane za pomocą websocket/sse lub po prostu frontend robi polling po świeże dane

Pisałem tutaj o usługach, ale równie dobrze może to być modularny monolit. 
Zamiast komunikacji asynchronicznej poprzez message bus, możemy zastosować facade pattern i moduły komunikują się za pomocą wzorca fasady.

# Pytania do wyjaśnienia

## Walidacja

Został jeszcze do rozwiązania problem walidacji. Docelowo pewnie dane byłyby walidowane w kontrolerze (walidator) - czy np. mamy teamId. 
Czy walidacja typu eventu to jest logika biznesowa, czy to tylko ograniczenie ze względu na PoC?
Czy mamy inne wymaganoia biznesowe co do poprawnosci danych?

## Powiadomienia klientów

`All clients receive event notifications` - jacy klienci? To są aplikacje frontend? 
Używają pollingu/websockets/sse? Czy klienci to inne microservices? 
A może tutaj trzeba zaimplementować obsługę wysyłania webhooks do wszystkich naszych klientów? - outbox pattern

Brakuje mi implementacji dla `All clients receive event notifications` - tutaj nie wiem dokładnie, o jakich klientów chodzi, ale jeśli są to:
- **aplikacje frontend** - tutaj mogą robić polling, możemy użyć websockets lub sse. Do obsługi websocket lub sse lepiej sprawdzi się usługa napisana w JS/TS, możemy jej dostarczyć dane za pomocą kolejki i eventu
- **inne microservices** - publikujemy event na kolejce i zainteresowane usługi ją dostaną
- **aplikacje klientów** - wysyłamy im webhooks - możemy zastosować outbox pattern i przy zapisie eventu do bazy dodajemy również wpis do tabelki z outbox, później worker obsługuje to i pewnie potrzebuje odczytać konfigurację dla webhooks i wysłać do odpowiednich aplikacji lub jeszcze raz dodać wpisy do outbox, ale już jeden wpis dla jednej aplikacji

Co jest ważne przy powiadomieniu aplikacji klientów? W evencie musimy dostarczyć dane z momentu otrzymania requestu z eventem. Czyli nie możemy im wysyłać np. matchId i oni mają pobrać sobie dane, bo te dane mogą być już inne - w między czasie zostały zaktualizowane.

# Code quality

Oczywiście w ramach CI można dodać jeszcze phpstan czy deptrac, ale w ramach PoC odpuszczam, bo mamy ograniczony czas. Dodałem tylko php-cs-fixer wraz z podstawowym CI. 


# AI w zadaniu 

AI wykorzystałem wyłacznie do poprawy stylistycznej i sformatowania tego dokumentu. Użyłem gemini-2.5-pro. 
Wszystkie obserwacje na temat projektu były wykonane samodzielnie jak i implementacja.
