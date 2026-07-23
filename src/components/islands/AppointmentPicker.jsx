import React, { useState, useEffect } from 'react';

const AVAILABILITY_API = '/api/availability.php';
const BOOK_API = '/api/book_appointment.php';

export default function AppointmentPicker({ sessionId, email, onBooked }) {
  const [slots, setSlots] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedDate, setSelectedDate] = useState(null);
  const [selectedSlot, setSelectedSlot] = useState(null);
  const [booking, setBooking] = useState(false);
  const [booked, setBooked] = useState(false);
  const [error, setError] = useState('');
  const [contactEmail, setContactEmail] = useState(email || '');
  const [appointmentType, setAppointmentType] = useState('diagnostico');

  useEffect(() => {
    fetchSlots();
  }, []);

  const fetchSlots = async () => {
    setLoading(true);
    try {
      const res = await fetch(AVAILABILITY_API);
      const data = await res.json();
      setSlots(data.slots || []);
    } catch (e) {
      setError('No se pudo cargar la disponibilidad.');
    } finally {
      setLoading(false);
    }
  };

  // Agrupar slots por fecha
  const dateMap = {};
  slots.forEach(s => {
    if (!dateMap[s.date]) dateMap[s.date] = [];
    dateMap[s.date].push(s);
  });
  const dates = Object.keys(dateMap).sort();

  const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
  const monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

  const formatDate = (dateStr) => {
    const d = new Date(dateStr + 'T12:00:00');
    return `${dayNames[d.getDay()]} ${d.getDate()} ${monthNames[d.getMonth()]}`;
  };

  const handleBook = async () => {
    if (!selectedSlot || !contactEmail) {
      setError('Selecciona un horario e ingresa tu email.');
      return;
    }
    setBooking(true);
    setError('');

    try {
      const res = await fetch(BOOK_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          session_id: sessionId || ('appt_' + Date.now()),
          email: contactEmail,
          requested_date: selectedSlot,
          type: appointmentType,
        }),
      });

      const data = await res.json();
      if (data.success) {
        setBooked(true);
        if (onBooked) onBooked(data);
      } else {
        setError(data.error || 'Error al reservar. Intenta con otro horario.');
        // Recargar slots en caso de doble-booking
        fetchSlots();
      }
    } catch (e) {
      setError('Error de conexión. Intenta de nuevo.');
    } finally {
      setBooking(false);
    }
  };

  if (booked) {
    return (
      <div className="bg-brand/10 border border-brandCyan/30 rounded-2xl p-4 text-center animate-in fade-in">
        <div className="text-3xl mb-2">✅</div>
        <p className="text-white font-bold mb-1">¡Solicitud registrada!</p>
        <p className="text-gray-400 text-sm">Un arquitecto de datos te confirmará la cita a la brevedad.</p>
      </div>
    );
  }

  return (
    <div className="bg-brand/5 border border-white/10 rounded-2xl p-4 animate-in fade-in">
      <h4 className="text-white font-bold text-sm mb-3 flex items-center gap-2">
        <span>📅</span> Agenda tu diagnóstico
      </h4>

      {loading ? (
        <p className="text-gray-400 text-xs text-center py-4">Cargando disponibilidad...</p>
      ) : dates.length === 0 ? (
        <p className="text-gray-400 text-xs text-center py-4">No hay slots disponibles en este momento.</p>
      ) : (
        <>
          {/* Selector de tipo */}
          <div className="flex gap-2 mb-3">
            <button
              onClick={() => setAppointmentType('diagnostico')}
              className={`text-xs px-3 py-1 rounded-full transition-all ${appointmentType === 'diagnostico' ? 'bg-brandCyan/20 text-brandCyan border border-brandCyan/50' : 'bg-white/5 text-gray-400 border border-white/10'}`}
            >
              Diagnóstico
            </button>
            <button
              onClick={() => setAppointmentType('reunion')}
              className={`text-xs px-3 py-1 rounded-full transition-all ${appointmentType === 'reunion' ? 'bg-brandCyan/20 text-brandCyan border border-brandCyan/50' : 'bg-white/5 text-gray-400 border border-white/10'}`}
            >
              Reunión
            </button>
          </div>

          {/* Selector de fecha */}
          <div className="flex gap-2 overflow-x-auto pb-2 mb-3" style={{ scrollbarWidth: 'thin' }}>
            {dates.slice(0, 10).map(d => (
              <button
                key={d}
                onClick={() => { setSelectedDate(d); setSelectedSlot(null); }}
                className={`flex-shrink-0 px-3 py-2 rounded-lg text-xs font-medium transition-all ${selectedDate === d ? 'bg-brandCyan/20 text-brandCyan border border-brandCyan/50' : 'bg-white/5 text-gray-400 border border-white/10 hover:bg-white/10'}`}
              >
                {formatDate(d)}
              </button>
            ))}
          </div>

          {/* Selector de hora */}
          {selectedDate && dateMap[selectedDate] && (
            <div className="grid grid-cols-3 gap-2 mb-3 max-h-32 overflow-y-auto" style={{ scrollbarWidth: 'thin' }}>
              {dateMap[selectedDate].map(s => (
                <button
                  key={s.datetime}
                  onClick={() => setSelectedSlot(s.datetime)}
                  className={`px-2 py-1.5 rounded-lg text-xs font-medium transition-all ${selectedSlot === s.datetime ? 'bg-brandCyan text-black font-bold' : 'bg-white/5 text-gray-300 border border-white/10 hover:bg-white/10'}`}
                >
                  {s.time}
                </button>
              ))}
            </div>
          )}

          {/* Email */}
          <input
            type="email"
            value={contactEmail}
            onChange={(e) => setContactEmail(e.target.value)}
            placeholder="Tu correo electrónico"
            className="w-full bg-darker border border-white/10 rounded-lg p-2 text-sm text-white mb-3 focus:outline-none focus:border-brandCyan"
          />

          {error && (
            <p className="text-red-400 text-xs mb-2">{error}</p>
          )}

          <button
            onClick={handleBook}
            disabled={!selectedSlot || !contactEmail || booking}
            className="w-full bg-brandCyan text-black font-bold py-2 rounded-lg text-sm transition-all hover:bg-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {booking ? 'Reservando...' : 'Solicitar cita'}
          </button>
          <p className="text-gray-500 text-xs mt-2 text-center">
            Zona horaria: América/Lima (UTC-5). La cita se confirma manualmente.
          </p>
        </>
      )}
    </div>
  );
}
