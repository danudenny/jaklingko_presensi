<script>
// Current date being viewed in the date drawer
let currentDate = null;
let currentScheduleId = null;

// Open the date drawer and fetch schedules for the given date
function openDateDrawer(date) {
    console.log('Opening date drawer for:', date);
    currentDate = date;
    
    // Show the drawer
    const drawer = document.getElementById('date-drawer');
    const panel = document.getElementById('date-drawer-panel');
    const backdrop = document.getElementById('date-drawer-backdrop');
    
    drawer.classList.remove('hidden');
    setTimeout(() => {
        panel.classList.remove('translate-x-full');
        backdrop.classList.add('opacity-100');
        backdrop.classList.remove('opacity-0');
    }, 10);
    
    // Show loading state
    document.getElementById('date-drawer-loading').classList.remove('hidden');
    document.getElementById('date-drawer-content').classList.add('hidden');
    document.getElementById('date-drawer-empty').classList.add('hidden');
    
    // Fetch schedules for this date
    fetchDateSchedules(date);
}

// Close the date drawer
function closeDateDrawer() {
    const drawer = document.getElementById('date-drawer');
    const panel = document.getElementById('date-drawer-panel');
    const backdrop = document.getElementById('date-drawer-backdrop');
    
    panel.classList.add('translate-x-full');
    backdrop.classList.remove('opacity-100');
    backdrop.classList.add('opacity-0');
    
    setTimeout(() => {
        drawer.classList.add('hidden');
    }, 300);
}

// Fetch schedules for a specific date
async function fetchDateSchedules(date) {
    try {
        const response = await fetch(`/schedules/date/${date}`);
        if (!response.ok) {
            throw new Error('Failed to fetch schedules');
        }
        
        const data = await response.json();
        console.log('Fetched schedules for date:', data);
        
        // Update the drawer with the fetched data
        updateDateDrawer(data);
    } catch (error) {
        console.error('Error fetching schedules:', error);
        document.getElementById('date-drawer-loading').classList.add('hidden');
        document.getElementById('date-drawer-empty').classList.remove('hidden');
    }
}

// Update the date drawer with the fetched data
function updateDateDrawer(data) {
    // Hide loading
    document.getElementById('date-drawer-loading').classList.add('hidden');
    
    // Update title and counts
    document.getElementById('date-formatted').textContent = data.formatted_date;
    document.getElementById('date-drawer-title').textContent = `Jadwal Tanggal ${data.formatted_date}`;
    document.getElementById('morning-count').textContent = data.morning_count;
    document.getElementById('evening-count').textContent = data.evening_count;
    document.getElementById('total-count').textContent = data.total_count;
    
    // Update "View All" link
    document.getElementById('view-all-link').href = `/schedules?date=${data.date}`;
    
    if (data.schedules.length === 0) {
        // Show empty state
        document.getElementById('date-drawer-empty').classList.remove('hidden');
    } else {
        // Show content
        document.getElementById('date-drawer-content').classList.remove('hidden');
        
        // Populate morning schedules
        const morningSchedules = data.schedules.filter(s => s.shift === 'pagi');
        const morningContainer = document.getElementById('morning-shift-container');
        const morningList = document.getElementById('morning-schedules');
        
        if (morningSchedules.length === 0) {
            morningContainer.classList.add('hidden');
        } else {
            morningContainer.classList.remove('hidden');
            morningList.innerHTML = '';
            
            morningSchedules.forEach(schedule => {
                morningList.innerHTML += `
                    <div class="bg-yellow-50 p-3 rounded-lg border border-yellow-100 hover:bg-yellow-100 transition cursor-pointer"
                         onclick="openScheduleDrawer(${schedule.id})">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-medium text-gray-900">${schedule.driver.name}</div>
                                <div class="text-sm text-gray-500">${schedule.driver.type === 'batangan' ? 'Pengemudi Batangan' : 'Pengemudi Cadangan'}</div>
                            </div>
                            <div class="text-right text-sm">
                                <div class="font-medium text-gray-900">${schedule.unit.unit_number}</div>
                                <div class="text-gray-500">${schedule.route.name}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        // Populate evening schedules
        const eveningSchedules = data.schedules.filter(s => s.shift === 'siang');
        const eveningContainer = document.getElementById('evening-shift-container');
        const eveningList = document.getElementById('evening-schedules');
        
        if (eveningSchedules.length === 0) {
            eveningContainer.classList.add('hidden');
        } else {
            eveningContainer.classList.remove('hidden');
            eveningList.innerHTML = '';
            
            eveningSchedules.forEach(schedule => {
                eveningList.innerHTML += `
                    <div class="bg-indigo-50 p-3 rounded-lg border border-indigo-100 hover:bg-indigo-100 transition cursor-pointer"
                         onclick="openScheduleDrawer(${schedule.id})">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-medium text-gray-900">${schedule.driver.name}</div>
                                <div class="text-sm text-gray-500">${schedule.driver.type === 'batangan' ? 'Pengemudi Batangan' : 'Pengemudi Cadangan'}</div>
                            </div>
                            <div class="text-right text-sm">
                                <div class="font-medium text-gray-900">${schedule.unit.unit_number}</div>
                                <div class="text-gray-500">${schedule.route.name}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
    }
}

// Open the schedule drawer and fetch details for the given schedule ID
function openScheduleDrawer(scheduleId) {
    console.log('Opening schedule drawer for ID:', scheduleId);
    currentScheduleId = scheduleId;
    
    // Show the drawer
    const drawer = document.getElementById('schedule-drawer');
    const panel = document.getElementById('schedule-drawer-panel');
    const backdrop = document.getElementById('schedule-drawer-backdrop');
    
    drawer.classList.remove('hidden');
    setTimeout(() => {
        panel.classList.remove('translate-x-full');
        backdrop.classList.add('opacity-100');
        backdrop.classList.remove('opacity-0');
    }, 10);
    
    // Show loading state
    document.getElementById('schedule-drawer-loading').classList.remove('hidden');
    document.getElementById('schedule-drawer-content').classList.add('hidden');
    document.getElementById('schedule-drawer-error').classList.add('hidden');
    
    // Fetch schedule details
    fetchScheduleDetails(scheduleId);
}

// Close the schedule drawer
function closeScheduleDrawer() {
    const drawer = document.getElementById('schedule-drawer');
    const panel = document.getElementById('schedule-drawer-panel');
    const backdrop = document.getElementById('schedule-drawer-backdrop');
    
    panel.classList.add('translate-x-full');
    backdrop.classList.remove('opacity-100');
    backdrop.classList.add('opacity-0');
    
    setTimeout(() => {
        drawer.classList.add('hidden');
    }, 300);
}

// Fetch details for a specific schedule
async function fetchScheduleDetails(scheduleId) {
    try {
        const response = await fetch(`/schedules/${scheduleId}`);
        if (!response.ok) {
            throw new Error('Failed to fetch schedule details');
        }
        
        const data = await response.json();
        console.log('Fetched schedule details:', data);
        
        // Update the drawer with the fetched data
        updateScheduleDrawer(data);
    } catch (error) {
        console.error('Error fetching schedule details:', error);
        document.getElementById('schedule-drawer-loading').classList.add('hidden');
        document.getElementById('schedule-drawer-error').classList.remove('hidden');
    }
}

// Update the schedule drawer with the fetched data
function updateScheduleDrawer(data) {
    // Hide loading
    document.getElementById('schedule-drawer-loading').classList.add('hidden');
    
    // Show content
    document.getElementById('schedule-drawer-content').classList.remove('hidden');
    
    // Update driver info
    document.getElementById('driver-name').textContent = data.driver.name;
    document.getElementById('driver-type').textContent = data.driver.type === 'batangan' ? 'Pengemudi Batangan' : 'Pengemudi Cadangan';
    
    // Update shift badge
    if (data.shift === 'pagi') {
        document.getElementById('shift-badge-morning').classList.remove('hidden');
        document.getElementById('shift-badge-evening').classList.add('hidden');
    } else {
        document.getElementById('shift-badge-morning').classList.add('hidden');
        document.getElementById('shift-badge-evening').classList.remove('hidden');
    }
    
    // Update schedule details
    const scheduleDate = new Date(data.schedule_date);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('schedule-date').textContent = scheduleDate.toLocaleDateString('id-ID', options);
    document.getElementById('route-name').textContent = data.route.name;
    document.getElementById('unit-info').textContent = `${data.unit.unit_number} - ${data.unit.plate_number}`;
    
    // Update backup driver info if available
    if (data.backup_driver_id) {
        document.getElementById('backup-driver-container').classList.remove('hidden');
        document.getElementById('backup-driver-name').textContent = data.backup_driver.name;
    } else {
        document.getElementById('backup-driver-container').classList.add('hidden');
    }
    
    // Update action buttons
    document.getElementById('edit-schedule-link').href = `/schedules/${data.id}/edit`;
    document.getElementById('delete-schedule-button').onclick = () => {
        if (confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) {
            window.location.href = `/schedules/${data.id}/delete`;
        }
    };
}

// Initialize event listeners when the document is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Calendar scripts initialized');
});
</script>
