# Maintenance Logs Excel Export

This document explains the enhanced Excel export functionality for maintenance logs.

## Overview

The export functionality creates an Excel file with two sheets:
1. **Maintenance Logs Summary** - Overview of all maintenance logs
2. **Detailed Breakdown** - Detailed view with cost breakdown and photo paths

## Features

### Summary Sheet
- Contains general information about each maintenance log
- Shows calculated total costs
- Displays basic information like unit, route, driver, status, etc.
- Includes creation and update timestamps

### Detailed Breakdown Sheet
- Provides row-by-row breakdown of each maintenance log
- **Cost Details**: Each cost item is listed as a separate row with description and amount
- **Photo Details**: Each photo is listed with its absolute URL path
- Photos use absolute URLs that can be accessed directly from the web

## Related Features

### Unit Problems - "Needs Repair" Toggle

A new feature has been added to the Unit Problems module that complements the maintenance log export:

#### Feature Overview
- **Toggle Control**: A visual toggle switch labeled "Butuh Perbaikan" (Needs Repair)
- **Purpose**: Indicates whether a reported unit problem requires repair work
- **Integration**: This field can help prioritize which unit problems should be converted to maintenance logs

#### UI/UX Details
- **Visual Toggle**: Modern slide toggle with green color when active
- **Real-time Label**: Label changes from "Tidak" (No) to "Ya" (Yes) when toggled
- **Forms**: Available in both Create and Edit forms for unit problems
- **Help Text**: "Centang jika masalah ini memerlukan perbaikan unit"

#### Database Schema
```sql
-- Migration: add_needs_repair_to_unit_problems_table
ALTER TABLE unit_problems ADD COLUMN needs_repair BOOLEAN DEFAULT FALSE;
```

#### Implementation Details
- **Model**: `UnitProblem` model updated with `needs_repair` in `$fillable` and `$casts`
- **Controller**: Both `store()` and `update()` methods handle the new field
- **Validation**: Boolean validation in form requests
- **Frontend**: Toggle switch with JavaScript for real-time label updates

### Usage Workflow
1. **Unit Problem Reporting**: When creating/editing a unit problem, use the toggle to indicate if repair is needed
2. **Problem Assessment**: The "Butuh Perbaikan" field helps maintenance staff prioritize issues
3. **Maintenance Conversion**: Problems marked as needing repair can be prioritized for conversion to maintenance logs
4. **Export Integration**: Maintenance logs created from these problems can be exported with full cost breakdown

## Enhanced Export Filters

The export supports the following advanced filters:

### Date Range Options
- **Custom Range**: Select specific start and end dates
- **Per Month**: Select a specific month and year
- **Per Year**: Select an entire year (January 1 - December 31)
- **YTD (Year to Date)**: From January 1 of current year to today
- **All Data**: Export all data without date filtering

### Chained Route and Unit Selection
- **Route First**: Select a route first from the dropdown
- **Units for Route**: After selecting a route, only units assigned to that route will be available
- **Multiple Unit Selection**: Select multiple units using checkboxes
- **Searchable Units**: Type to search for specific units within the selected route
- **"All Units" Option**: Select all units within the chosen route

### Route Selection
- **Dropdown Selection**: Choose specific route or all routes
- **Unit Dependency**: Unit selection depends on route selection

## Usage

### From Admin Panel
1. Navigate to **Log Perawatan** page
2. Click the **Export Excel** button
3. Select your desired date range type:
   - For **Custom**: Set start and end dates
   - For **Per Bulan**: Choose month and year
   - For **Per Tahun**: Choose year
   - For **YTD** or **Semua Data**: No additional input needed
4. **Select Route**: Choose a route from the dropdown (this will enable unit selection)
5. **Select Units**: 
   - Multiple units can be selected using checkboxes
   - Use the search field to quickly find specific units
   - Check "Semua Unit" to select all units for the chosen route
   - Selected units will appear as tags below the search field
6. Click **Export ke Excel** to download

### Chained Filter Workflow
1. **Route Selection**: Choose a route → Units for that route become available
2. **Unit Selection**: Choose one or more units from the filtered list
3. **Search Function**: Type unit numbers to filter the available units
4. **Multiple Selection**: Check multiple units as needed
5. **Visual Feedback**: Selected units appear as removable tags

### Unit Search and Selection Features
- **Route Dependency**: Units are filtered based on the selected route
- **Live Search**: Type to filter units in real-time
- **Multiple Selection**: Use checkboxes to select multiple units
- **Select All**: Use "Semua Unit" checkbox to select all units in the route
- **Clear All**: Use "Clear All" button to deselect all units
- **Visual Tags**: Selected units appear as blue tags with remove buttons
- **Remove Individual**: Click the × button on any tag to remove that unit

### Programmatically
```php
// Basic export with new structure
return Excel::download(new MaintenanceLogsExport(), 'maintenance_logs.xlsx');

// With multiple unit IDs (comma-separated string or array)
return Excel::download(
    new MaintenanceLogsExport($startDate, $endDate, null, '1,2,3', $routeId), 
    'maintenance_logs_filtered.xlsx'
);

// With array of unit IDs
return Excel::download(
    new MaintenanceLogsExport($startDate, $endDate, null, [1, 2, 3], $routeId), 
    'maintenance_logs_filtered.xlsx'
);
```

## File Naming Convention

Files are automatically named based on the selected date range:
- **Custom**: `maintenance_logs_2025-01-15_to_2025-01-31.xlsx`
- **Per Bulan**: `maintenance_logs_Jan_2025.xlsx`
- **Per Tahun**: `maintenance_logs_tahun_2025.xlsx`
- **YTD**: `maintenance_logs_YTD_2025.xlsx`
- **All Data**: `maintenance_logs_semua_data_2025-06-18.xlsx`

## File Structure

### Summary Sheet Columns
- No
- Tanggal Laporan
- Waktu Laporan
- Unit
- Rute
- Pengemudi
- Deskripsi
- Tipe
- Suku Cadang
- Kategori
- Sumber Suku Cadang
- Status
- Dalam Jadwal
- Total Biaya (calculated)
- Jumlah Foto
- Tanggal Dibuat
- Tanggal Diperbarui

### Detailed Breakdown Sheet Columns
- Log ID
- Tanggal Laporan
- Waktu Laporan
- Unit
- Rute
- Pengemudi
- Deskripsi
- Tipe
- Suku Cadang
- Kategori
- Sumber Suku Cadang
- Status
- Dalam Jadwal
- Tipe Detail (Cost/Photo)
- Index Detail
- Deskripsi Biaya
- Jumlah Biaya
- Path Foto (Absolute URL)
- Tanggal Dibuat
- Tanggal Diperbarui

## Image Path Format

Photos are exported with absolute URLs in the format:
```
https://yourdomain.com/storage/maintenance-logs/photo-filename.jpg
```

This allows direct access to images from the Excel file.

## Cost Breakdown

Each maintenance log can have multiple cost items. In the detailed breakdown:
- Each cost item appears as a separate row
- Shows description and amount for each cost
- Costs are formatted as "Rp X,XXX,XXX"
- If no costs exist, shows "No cost details"

## Technical Details

### Classes
- `MaintenanceLogsExport`: Main export class with multiple sheets
- `MaintenanceLogsSummarySheet`: Summary sheet implementation
- `MaintenanceLogsDetailedSheet`: Detailed breakdown sheet implementation

### Dependencies
- `maatwebsite/excel` package for Excel generation
- Laravel's Eloquent for data retrieval
- Carbon for date formatting

### Styling
- Headers have blue background with white text (Summary) / green background (Detailed)
- All cells have borders for better readability
- Auto-sizing columns for optimal display
- Custom CSS for unit search dropdown

### JavaScript Features
- Dynamic date range input toggling
- Live unit search functionality
- Form state management
- Default value setting

## Routes

- `GET /maintenance-logs-export` - Show export form
- `POST /maintenance-logs-export` - Process export with filters
- `GET /admin/maintenance-logs/units-for-route/{routeId}` - Get units for a specific route (AJAX endpoint)

## Examples

### Date Range Examples

#### Month Selection
Selecting "Juni 2025" will export data from:
- Start: 2025-06-01
- End: 2025-06-30

#### Year Selection
Selecting "2025" will export data from:
- Start: 2025-01-01
- End: 2025-12-31

#### YTD Selection
For current year 2025, will export data from:
- Start: 2025-01-01
- End: 2025-06-18 (current date)

### Sample Cost Breakdown
If a maintenance log has costs:
```json
[
    {"description": "Oli mesin", "amount": 150000},
    {"description": "Filter udara", "amount": 75000}
]
```

This will create two rows in the detailed sheet:
- Row 1: Cost details for "Oli mesin" - Rp 150,000
- Row 2: Cost details for "Filter udara" - Rp 75,000

### Sample Photo Paths
Photos will appear as:
```
https://yourdomain.com/storage/maintenance-logs/maintenance_photo_1.jpg
https://yourdomain.com/storage/maintenance-logs/maintenance_photo_2.jpg
```

## Changes from Previous Version

1. **Removed Status Filter**: Status filtering has been removed from the export form
2. **Enhanced Date Range Options**: Added Month, Year, YTD, and All Data options
3. **Chained Route-Unit Selection**: Route must be selected first, then units for that route become available
4. **Multiple Unit Selection**: Changed from single unit selection to multiple unit selection using checkboxes
5. **Searchable Unit Selector**: Units can be searched within the selected route
6. **Smart File Naming**: Automatic filename generation based on selected filters
7. **Improved UI**: Better styling and user experience with visual tags for selected units
8. **Default Values**: Intelligent default value setting based on current date
9. **AJAX Unit Loading**: Units are loaded dynamically based on route selection
10. **Enhanced Visual Feedback**: Selected units appear as removable tags
11. **Route Dependency**: Unit selection is now dependent on route selection for better data integrity
