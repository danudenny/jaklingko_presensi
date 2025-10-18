# Shift Color Scheme - Visual Guide

## Updated Color Coding for Pagi and Siang Shifts

### Overview
The schedule view now uses distinct colors to differentiate between Pagi (Morning) and Siang (Afternoon) shifts, making it easier to identify shift patterns at a glance.

---

## Color Scheme

### **Shift Pagi (P) - Blue Tones**

| Driver Type | Color | Background | Text | Hover |
|-------------|-------|------------|------|-------|
| **Batangan** | Blue (Dark) | `bg-blue-100` | `text-blue-800` | `hover:bg-blue-200` |
| **Cadangan** | Sky (Light) | `bg-sky-100` | `text-sky-800` | `hover:bg-sky-200` |

**Visual Example:**
- Batangan P: 🟦 (Dark Blue)
- Cadangan P: 🔷 (Light Blue/Sky)

---

### **Shift Siang (S) - Orange/Amber Tones**

| Driver Type | Color | Background | Text | Hover |
|-------------|-------|------------|------|-------|
| **Batangan** | Orange | `bg-orange-100` | `text-orange-800` | `hover:bg-orange-200` |
| **Cadangan** | Amber | `bg-amber-100` | `text-amber-800` | `hover:bg-amber-200` |

**Visual Example:**
- Batangan S: 🟧 (Orange)
- Cadangan S: 🟨 (Amber/Yellow)

---

### **Both Shifts (P+S) - Purple**

| Display | Color | Background | Text | Hover |
|---------|-------|------------|------|-------|
| P+S | Purple | `bg-purple-100` | `text-purple-800` | `hover:bg-purple-200` |

**Visual Example:**
- P+S: 🟪 (Purple) - Same for both driver types

---

### **Special Status**

| Status | Code | Color | Background | Text |
|--------|------|-------|------------|------|
| **Backup** | B | Yellow | `bg-yellow-100` | `text-yellow-800` |
| **Off/Leave** | OFF | Red | `bg-red-100` | `text-red-800` |
| **Maintenance** | M | Teal | `bg-teal-100` | `text-teal-800` |
| **Renops** | R | Orange (special) | `renops-indicator` | - |
| **Not Scheduled** | - | Gray | `border-gray-200` | `text-gray-300` |

---

## Visual Pattern Examples

### Example 1: Batangan Driver (6-1 Cycle, Pagi Only)
```
H. ABDUL MUNIR (Batangan):
🟦 🟦 🟦 🟦 🟦 🟦 🔴 🟦 🟦 🟦 🟦 🟦 🟦 🔴
P   P   P   P   P   P  OFF  P   P   P   P   P   P  OFF
```

### Example 2: Cadangan Driver (Covering Siang)
```
FIRMANDO (Cadangan):
🟨 -  🟨 -  🟨 -  -  🟨 -  🟨 -  🟨 -  -
S  -   S  -   S  -  -   S  -   S  -   S  -  -
```

### Example 3: Mixed Shifts
```
DRIVER X:
🟦 🟧 🟪 -  🟦 -  🟧 -  -  🟦 🟧 -  -  -
P   S  P+S -   P  -   S  -  -   P   S  -  -  -
```

---

## Benefits of Color Separation

### 1. **Quick Visual Identification**
- Blue = Morning shifts (Pagi)
- Orange/Amber = Afternoon shifts (Siang)
- Purple = Both shifts
- Red = Off days

### 2. **Driver Type Distinction**
- **Darker shade** = Batangan driver
- **Lighter shade** = Cadangan driver

### 3. **Pattern Recognition**
- Easily spot 6-1 cycle patterns for batangan drivers
- Identify which shift cadangan drivers are covering
- See at a glance if someone works both shifts

### 4. **Accessibility**
- High contrast between different shift types
- Color-blind friendly (different hues, not just colors)
- Clear visual hierarchy

---

## Legend Summary

**Schedule View Legend:**

| Symbol | Meaning | Color |
|--------|---------|-------|
| **P** (Dark Blue) | Shift Pagi - Batangan | 🟦 |
| **P** (Light Blue) | Shift Pagi - Cadangan | 🔷 |
| **S** (Orange) | Shift Siang - Batangan | 🟧 |
| **S** (Amber) | Shift Siang - Cadangan | 🟨 |
| **P+S** (Purple) | Kedua Shift | 🟪 |
| **B** (Yellow) | Backup | 🟡 |
| **OFF** (Red) | Cuti/Libur | 🔴 |
| **M** (Teal) | Maintenance | 🟢 |
| **R** (Orange) | Renops | ⚠️ |
| **-** (Gray) | Not Scheduled | ⚪ |

---

## Use Cases

### 1. Identifying Shift Coverage Gaps
Look for columns with only one color:
- Only Blue (P) = Need Siang coverage
- Only Orange (S) = Need Pagi coverage

### 2. Verifying 6-1 Cycle Pattern
Batangan drivers should show:
- 6 consecutive Blue (P) cells
- Followed by 1 Red (OFF) cell
- Pattern repeats

### 3. Cadangan Distribution Analysis
Check if cadangan drivers (lighter shades) are evenly distributed across:
- Different days of the week
- Different shifts (Pagi vs Siang)
- Different units

### 4. Monthly Overview
- Count Blue cells = Total Pagi shifts
- Count Orange/Amber cells = Total Siang shifts
- Count Purple cells = Total double shifts
- Count Red cells = Total off days

---

## Technical Implementation

**Color Assignment Logic:**
```php
if ($pagiAssigned && $siangAssigned) {
    // Both shifts - Purple
    $shiftColorClass = 'bg-purple-100 text-purple-800';
} elseif ($pagiAssigned) {
    // Pagi - Blue (batangan) or Sky (cadangan)
    $shiftColorClass = $isBatangan 
        ? 'bg-blue-100 text-blue-800' 
        : 'bg-sky-100 text-sky-800';
} elseif ($siangAssigned) {
    // Siang - Orange (batangan) or Amber (cadangan)
    $shiftColorClass = $isBatangan 
        ? 'bg-orange-100 text-orange-800' 
        : 'bg-amber-100 text-amber-800';
}
```

---

## Conclusion

The new color scheme provides clear visual distinction between:
- ✅ Pagi vs Siang shifts (Blue vs Orange)
- ✅ Batangan vs Cadangan drivers (Darker vs Lighter shades)
- ✅ Working vs Off days (Colors vs Red)
- ✅ Special statuses (Purple, Yellow, Teal, etc.)

This makes the schedule matrix much easier to read and analyze at a glance!
