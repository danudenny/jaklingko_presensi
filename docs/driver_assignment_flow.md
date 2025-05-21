# Driver Assignment Process Flow

## Overview Diagram

```mermaid
flowchart TD
    Start([Start Schedule Generation]) --> LoadData[Load Data]
    LoadData --> CreateDriverUnitMap[Create Driver-Unit Map]
    CreateDriverUnitMap --> ProcessDates[Process Each Date in Range]
    ProcessDates --> ProcessUnits[Process Available Units]
    ProcessUnits --> FindDriver[Find Suitable Driver]
    
    FindDriver --> CheckBatangan{Check Batangan\nDrivers for Unit}
    CheckBatangan -->|Found| FilterBatangan[Filter Suitable\nBatangan Drivers]
    CheckBatangan -->|Not Found| CheckUnitCadangan{Check Cadangan\nDrivers for Unit}
    
    FilterBatangan --> FoundBatangan{Found\nSuitable?}
    FoundBatangan -->|Yes| AssignBatangan[Assign Batangan Driver]
    FoundBatangan -->|No| CheckUnitCadangan
    
    CheckUnitCadangan -->|Found| FilterUnitCadangan[Filter Suitable\nUnit Cadangan Drivers]
    CheckUnitCadangan -->|Not Found| CheckRouteCadangan{Check Cadangan\nDrivers for Route}
    
    FilterUnitCadangan --> FoundUnitCadangan{Found\nSuitable?}
    FoundUnitCadangan -->|Yes| AssignUnitCadangan[Assign Unit Cadangan Driver]
    FoundUnitCadangan -->|No| CheckRouteCadangan
    
    CheckRouteCadangan -->|Found| FilterRouteCadangan[Filter Suitable\nRoute Cadangan Drivers]
    CheckRouteCadangan -->|Not Found| NoDriver[No Suitable Driver Found]
    
    FilterRouteCadangan --> FoundRouteCadangan{Found\nSuitable?}
    FoundRouteCadangan -->|Yes| AssignRouteCadangan[Assign Route Cadangan Driver]
    FoundRouteCadangan -->|No| NoDriver
    
    AssignBatangan --> NextUnit[Process Next Unit]
    AssignUnitCadangan --> NextUnit
    AssignRouteCadangan --> NextUnit
    NoDriver --> NextUnit
    
    NextUnit --> CheckMoreUnits{More Units?}
    CheckMoreUnits -->|Yes| ProcessUnits
    CheckMoreUnits -->|No| CheckMoreDates{More Dates?}
    
    CheckMoreDates -->|Yes| ProcessDates
    CheckMoreDates -->|No| End([End Schedule Generation])
```

## Driver Filtering Process

```mermaid
flowchart TD
    Start([Start Driver Filtering]) --> AlreadyScheduled{Already\nScheduled?}
    AlreadyScheduled -->|Yes| NotSuitable([Not Suitable])
    AlreadyScheduled -->|No| OnLeave{On Leave?}
    
    OnLeave -->|Yes| NotSuitable
    OnLeave -->|No| SameDayShift{Already Scheduled\nSame Day\nDifferent Shift?}
    
    SameDayShift -->|Yes| NotSuitable
    SameDayShift -->|No| CheckDriverType{Driver Type?}
    
    CheckDriverType -->|Batangan| StrictAssignment{Strict Unit\nAssignment?}
    CheckDriverType -->|Cadangan| ShiftSequence{Check Shift\nSequence Rules}
    
    StrictAssignment -->|Yes| OtherUnitSchedules{Has Schedules\nfor Other Units?}
    StrictAssignment -->|No| ShiftSequence
    
    OtherUnitSchedules -->|Yes| NotSuitable
    OtherUnitSchedules -->|No| ShiftSequence
    
    ShiftSequence -->|Violates Rules| NotSuitable
    ShiftSequence -->|Follows Rules| WorkdayLimit{Exceeds\nWorkday Limit?}
    
    WorkdayLimit -->|Yes| NotSuitable
    WorkdayLimit -->|No| Suitable([Suitable Driver])
```

## Priority System for Driver Assignment

```mermaid
flowchart TD
    Start([Start Driver Selection]) --> Priority1[1. Batangan drivers assigned to specific unit]
    Priority1 --> Priority2[2. Cadangan drivers assigned to specific unit]
    Priority2 --> Priority3[3. Cadangan drivers qualified for route but not assigned to unit]
    Priority3 --> End([End Driver Selection])
    
    style Priority1 fill:#f9f,stroke:#333,stroke-width:2px
    style Priority2 fill:#bbf,stroke:#333,stroke-width:2px
    style Priority3 fill:#dfd,stroke:#333,stroke-width:2px
```

## Driver-Unit Assignment Map Structure

```mermaid
classDiagram
    class DriverUnitMap {
        driverId1 → [unitId1, unitId2, ...]
        driverId2 → [unitId3, unitId4, ...]
        ...
    }
    
    class Driver {
        id
        name
        type (batangan/cadangan)
    }
    
    class Unit {
        id
        unit_number
        route_id
    }
    
    Driver "1" -- "*" Unit : assigned to
```

## Data Model Relationships

```mermaid
erDiagram
    DRIVER ||--o{ DRIVER_UNITS : has
    DRIVER ||--o{ DRIVER_ROUTES : qualified_for
    UNIT ||--o{ DRIVER_UNITS : assigned_to
    ROUTE ||--o{ DRIVER_ROUTES : has_qualified_drivers
    UNIT ||--|| ROUTE : belongs_to
    SCHEDULE ||--|| DRIVER : assigned_to
    SCHEDULE ||--|| UNIT : uses
    SCHEDULE ||--|| ROUTE : follows
    
    DRIVER {
        int id
        string name
        string type
    }
    
    UNIT {
        int id
        string unit_number
        int route_id
    }
    
    ROUTE {
        int id
        string name
    }
    
    DRIVER_UNITS {
        int driver_id
        int unit_id
    }
    
    DRIVER_ROUTES {
        int driver_id
        int route_id
    }
    
    SCHEDULE {
        int id
        date schedule_date
        string shift
        int driver_id
        int unit_id
        int route_id
    }
```
