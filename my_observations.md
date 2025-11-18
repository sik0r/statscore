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

# Architektura rozwiazania

Czy w ramach PoC stosować hexagonal architecture? Czy może wystarczy jak dodam tylko katalog Infrastructure i tam umieszcze implementacje
FileStorage? Jest sens dodawać Application i Domain? Nie mam modelu danych pod statystyki - trzeba dodać?
