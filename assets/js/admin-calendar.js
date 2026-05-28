import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('admin-calendar');
    if (!el) return;

    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
        locale: frLocale,
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
        },
        buttonText: {
            today: "Aujourd'hui",
            month: 'Mois',
            week: 'Semaine',
            day: 'Jour',
            list: 'Liste',
        },
        slotMinTime: '08:00:00',
        slotMaxTime: '20:00:00',
        allDaySlot: false,
        nowIndicator: true,
        height: 'auto',
        events: '/admin/api/bookings/events',
        eventClick(info) {
            const { bookingId, clientName, serviceName, animalName, price, adminNotes } = info.event.extendedProps;
            const lines = [
                `Client : ${clientName}`,
                `Service : ${serviceName}`,
                animalName ? `Animal : ${animalName}` : null,
                price ? `Tarif : ${price} €` : null,
                adminNotes ? `Notes : ${adminNotes}` : null,
            ].filter(Boolean).join('\n');
            alert(`Réservation #${bookingId}\n\n${lines}`);
        },
        eventDidMount(info) {
            info.el.title = info.event.title;
        },
    });

    calendar.render();
});
