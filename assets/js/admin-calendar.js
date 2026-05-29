import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('admin-calendar');
    if (!el) return;

    // --- Modale ---
    const backdrop = document.getElementById('cal-modal-backdrop');
    const modalTitle = document.getElementById('cal-modal-title');
    const modalBody  = document.getElementById('cal-modal-body');
    const modalLink  = document.getElementById('cal-modal-link');
    const btnClose   = document.getElementById('cal-modal-close');
    const btnCancel  = document.getElementById('cal-modal-cancel');

    function openModal(event) {
        const p = event.extendedProps;
        const start = event.start;
        const end   = event.end;

        modalTitle.textContent = `Réservation #${p.bookingId}`;

        const statusLabels = {
            pending:   'En attente',
            confirmed: 'Confirmée',
            completed: 'Terminée',
            refused:   'Refusée',
            cancelled: 'Annulée',
        };
        const statusColors = {
            pending:   '#f59e0b',
            confirmed: '#10b981',
            completed: '#6b7280',
            refused:   '#dc2626',
            cancelled: '#9ca3af',
        };

        const fmt = (dt) => dt ? dt.toLocaleString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' }) : null;

        const rows = [
            ['Client',   p.clientName],
            ['Service',  p.serviceName],
            p.animalName ? ['Animal', p.animalName] : null,
            fmt(start)   ? ['Début',  fmt(start)]   : null,
            fmt(end)     ? ['Fin',    fmt(end)]      : null,
            p.price      ? ['Tarif',  p.price + ' €'] : null,
            p.adminNotes ? ['Notes',  p.adminNotes]  : null,
            ['Statut',   `<span style="color:${statusColors[p.status] ?? '#fff'};font-weight:600;">${statusLabels[p.status] ?? p.status}</span>`],
        ].filter(Boolean);

        modalBody.innerHTML = rows.map(([label, value]) =>
            `<dt style="color:#9aaccf;white-space:nowrap;">${label}</dt><dd style="margin:0;color:#e0e0e0;">${value}</dd>`
        ).join('');

        const adminUrl = `/admin?crudControllerFqcn=App%5CController%5CAdmin%5CBookingCrudController&crudAction=detail&entityId=${p.bookingId}`;
        modalLink.href = adminUrl;

        backdrop.style.display = 'flex';
        btnCancel.focus();
    }

    function closeModal() {
        backdrop.style.display = 'none';
    }

    if (btnClose)  btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);
    if (backdrop)  backdrop.addEventListener('click', (e) => { if (e.target === backdrop) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    // --- Calendrier ---
    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
        locale: frLocale,
        initialView: 'dayGridMonth',
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
        height: '100%',
        expandRows: true,
        events: '/admin/api/bookings/events',
        eventClick(info) {
            openModal(info.event);
        },
        eventDidMount(info) {
            info.el.title = '';
        },
    });

    calendar.render();
});
