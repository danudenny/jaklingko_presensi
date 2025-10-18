# Batangan 6-1 Cycle Pattern - Visual Guide

## The Pattern

```
┌─────┬─────┬─────┬─────┬─────┬─────┬─────┐
│  1  │  2  │  3  │  4  │  5  │  6  │  7  │ ← Cycle Day
├─────┼─────┼─────┼─────┼─────┼─────┼─────┤
│  v  │  v  │  v  │  v  │  v  │  v  │  x  │ ← Status
├─────┼─────┼─────┼─────┼─────┼─────┼─────┤
│Work │Work │Work │Work │Work │Work │ OFF │ ← Activity
└─────┴─────┴─────┴─────┴─────┴─────┴─────┘
                                      │
                                      ↓
              Cadangan Driver Covers This Day
```

## 30-Day Example

```
Week 1:  Mon  Tue  Wed  Thu  Fri  Sat  Sun
Day:      1    2    3    4    5    6    7
Cycle:    1    2    3    4    5    6    7
Bat A:    v    v    v    v    v    v    x    ← PAGI shift
Bat B:    v    v    v    v    v    v    x    ← SIANG shift
Cad:      -    -    -    -    -    -    ✓    ← Covers both shifts

Week 2:  Mon  Tue  Wed  Thu  Fri  Sat  Sun
Day:      8    9   10   11   12   13   14
Cycle:    1    2    3    4    5    6    7
Bat A:    v    v    v    v    v    v    x    ← PAGI shift
Bat B:    v    v    v    v    v    v    x    ← SIANG shift
Cad:      -    -    -    -    -    -    ✓    ← Covers both shifts

Week 3:  Mon  Tue  Wed  Thu  Fri  Sat  Sun
Day:     15   16   17   18   19   20   21
Cycle:    1    2    3    4    5    6    7
Bat A:    v    v    v    v    v    v    x    ← PAGI shift
Bat B:    v    v    v    v    v    v    x    ← SIANG shift
Cad:      -    -    -    -    -    -    ✓    ← Covers both shifts

Week 4:  Mon  Tue  Wed  Thu  Fri  Sat  Sun
Day:     22   23   24   25   26   27   28
Cycle:    1    2    3    4    5    6    7
Bat A:    v    v    v    v    v    v    x    ← PAGI shift
Bat B:    v    v    v    v    v    v    x    ← SIANG shift
Cad:      -    -    -    -    -    -    ✓    ← Covers both shifts

Extra:   Mon  Tue
Day:     29   30
Cycle:    1    2
Bat A:    v    v    ← PAGI shift
Bat B:    v    v    ← SIANG shift

Legend:
  v = Working (batangan driver assigned)
  x = Off day (batangan driver rests)
  ✓ = Cadangan covers
  - = Batangan working, no cadangan needed
```

## Cycle Flow Diagram

```
┌───────────────────────────────────────┐
│         START OF CYCLE                │
│         (Day 1 of 7)                  │
└─────────────┬─────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  BATANGAN DRIVER WORKS              │
│  Days 1 → 2 → 3 → 4 → 5 → 6         │
│  (6 consecutive working days)       │
└─────────────┬───────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  DAY 7: OFF DAY                     │
│  • Batangan driver rests            │
│  • Cadangan driver covers shift     │
│  • Schedule marked as               │
│    'cadangan_cover'                 │
└─────────────┬───────────────────────┘
              ↓
┌─────────────────────────────────────┐
│  CYCLE RESETS                       │
│  Next day becomes Day 1 again       │
│  Pattern repeats                    │
└─────────────────────────────────────┘
```

## Driver Rotation Example

### Two Batangan Drivers in Same Unit

```
Driver A (PAGI shift):
┌──────────────────────────────────────────────┐
│ Day:    1  2  3  4  5  6  7  8  9 10 11 12   │
│ Cycle:  1  2  3  4  5  6  7  1  2  3  4  5   │
│ Status: v  v  v  v  v  v  x  v  v  v  v  v   │
└──────────────────────────────────────────────┘

Driver B (SIANG shift):
┌──────────────────────────────────────────────┐
│ Day:    1  2  3  4  5  6  7  8  9 10 11 12   │
│ Cycle:  1  2  3  4  5  6  7  1  2  3  4  5   │
│ Status: v  v  v  v  v  v  x  v  v  v  v  v   │
└──────────────────────────────────────────────┘

Both drivers follow SAME cycle pattern
Both have OFF on same days (7, 14, 21, 28)
Cadangan covers BOTH shifts on those days
```

## Database Tracking

### Schedules Table Fields

```
┌─────────────┬────────────┬────────────────────┐
│ Field       │ Example    │ Description        │
├─────────────┼────────────┼────────────────────┤
│ driver_id   │ 12         │ Batangan driver    │
│ shift       │ 'pagi'     │ Morning shift      │
│ cycle_day   │ 3          │ Day 3 of cycle     │
│ type        │ 'regular'  │ Normal assignment  │
└─────────────┴────────────┴────────────────────┘

On OFF day (Day 7):
┌─────────────┬─────────────┬────────────────────┐
│ Field       │ Example     │ Description        │
├─────────────┼─────────────┼────────────────────┤
│ driver_id   │ 34          │ CADANGAN driver    │
│ shift       │ 'pagi'      │ Morning shift      │
│ cycle_day   │ NULL        │ No cycle tracking  │
│ type        │ 'cadangan_  │ Covering batangan  │
│             │  cover'     │ off day            │
└─────────────┴─────────────┴────────────────────┘
```

## Decision Tree

```
                    ┌─────────────────┐
                    │  Generate       │
                    │  Schedule for   │
                    │  Date X         │
                    └────────┬────────┘
                             ↓
                    ┌─────────────────┐
                    │  Get Batangan   │
                    │  Drivers        │
                    └────────┬────────┘
                             ↓
              ┌──────────────┴──────────────┐
              ↓                             ↓
    ┌─────────────────┐           ┌─────────────────┐
    │ Driver A        │           │ Driver B        │
    │ (PAGI shift)    │           │ (SIANG shift)   │
    └────────┬────────┘           └────────┬────────┘
             ↓                              ↓
    ┌─────────────────┐           ┌─────────────────┐
    │ Get Cycle Day   │           │ Get Cycle Day   │
    │ (1-7)           │           │ (1-7)           │
    └────────┬────────┘           └────────┬────────┘
             ↓                              ↓
    ┌─────────────────┐           ┌─────────────────┐
    │ Is Day 7?       │           │ Is Day 7?       │
    └────┬───────┬────┘           └────┬───────┬────┘
         │Yes    │No                   │Yes    │No
         ↓       ↓                     ↓       ↓
    ┌────────┐ ┌──────────┐     ┌────────┐ ┌──────────┐
    │ SKIP   │ │ ASSIGN   │     │ SKIP   │ │ ASSIGN   │
    │ (OFF)  │ │ to PAGI  │     │ (OFF)  │ │ to SIANG │
    └────┬───┘ └────┬─────┘     └────┬───┘ └────┬─────┘
         │          │                │          │
         └──────────┴────────────────┴──────────┘
                    ↓
           ┌─────────────────┐
           │ Any shifts      │
           │ not assigned?   │
           └────────┬────────┘
                    ↓
           ┌─────────────────┐
           │ Fill with       │
           │ CADANGAN        │
           │ drivers         │
           └─────────────────┘
```

## Monthly Overview (30 Days)

```
╔════════════════════════════════════════════════════════════╗
║  Month View: October 2025                                  ║
╠════════════════════════════════════════════════════════════╣
║                                                            ║
║  Week 1:  [v][v][v][v][v][v][x]     ← Days 1-7            ║
║  Week 2:  [v][v][v][v][v][v][x]     ← Days 8-14           ║
║  Week 3:  [v][v][v][v][v][v][x]     ← Days 15-21          ║
║  Week 4:  [v][v][v][v][v][v][x]     ← Days 22-28          ║
║  Extra:   [v][v]                    ← Days 29-30          ║
║                                                            ║
║  Working Days: 26 days (6+6+6+6+2)                        ║
║  Off Days: 4 days (7, 14, 21, 28)                         ║
║                                                            ║
║  [v] = Batangan works     [x] = Batangan off (Cad covers) ║
╚════════════════════════════════════════════════════════════╝
```

## Comparison: Old vs New

### OLD Pattern (Weekend-based)
```
Mon Tue Wed Thu Fri Sat Sun
 v   v   v   v   v   ?   ?    ← Unpredictable weekends
 v   v   v   v   v   ?   ?    ← Hash-based off days
 v   v   v   v   v   ?   ?    ← Complex logic
```

### NEW Pattern (6-1 Cycle)
```
Day Day Day Day Day Day Day
 1   2   3   4   5   6   7
 v   v   v   v   v   v   x    ← Predictable cycle
 v   v   v   v   v   v   x    ← Always same pattern
 v   v   v   v   v   v   x    ← Simple & clear
```

## Key Metrics

```
╔══════════════════════════════════════════╗
║  30-Day Period Statistics                ║
╠══════════════════════════════════════════╣
║                                          ║
║  Total Days:              30 days        ║
║  Batangan Work Days:      26 days (87%)  ║
║  Batangan Off Days:       4 days (13%)   ║
║  Cadangan Coverage Days:  4 days         ║
║                                          ║
║  Complete Cycles:         4 cycles       ║
║  Partial Cycle:           2 days         ║
║                                          ║
║  Per Batangan Driver:                    ║
║    - Works: 26 shifts/month              ║
║    - Rests: 4 days/month                 ║
║    - Within limit: Yes (max 12)          ║
║                                          ║
╚══════════════════════════════════════════╝

Note: Monthly limit of 12 shifts may stop 
assignment before reaching 26 days
```

## Health & Safety Benefits

```
┌────────────────────────────────────────┐
│  Regular Rest Pattern Benefits         │
├────────────────────────────────────────┤
│                                        │
│  ✓ Prevents driver fatigue             │
│  ✓ Reduces accident risk               │
│  ✓ Improves alertness                  │
│  ✓ Better work-life balance            │
│  ✓ Predictable personal time           │
│  ✓ Meets labor regulations             │
│  ✓ Fair for all drivers                │
│                                        │
└────────────────────────────────────────┘
```

## Summary

The 6-1 cycle pattern is:

```
╔════════════════════════════════════════╗
║  SIMPLE:    Easy to understand         ║
║  FAIR:      Same for all drivers       ║
║  HEALTHY:   Regular rest periods       ║
║  TRACKED:   Database cycle_day field   ║
║  RELIABLE:  Predictable schedule       ║
╚════════════════════════════════════════╝
```

Pattern Formula:
```
WORK 6 days → REST 1 day → REPEAT
```

That's it! Simple, effective, healthy. 🚌✨
