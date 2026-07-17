import React, { useState } from 'react';
import { SAVE_WIZARD_API } from '../../lib/endpoints';

export default function BusinessCaseEstimator() {
  const [revenue, setRevenue] = useState(5000000);
  const [costBase, setCostBase] = useState(2000000);
  const [efficiencyGain, setEfficiencyGain] = useState(20);
  const [addressablePercentage, setAddressablePercentage] = useState(25);
  const [calculated, setCalculated] = useState(false);
  
  const [leadData, setLeadData] = useState({ email: '', telefono: '' });
  const [leadSaved, setLeadSaved] = useState(false);

  const MAINTENANCE_PERCENTAGE = 0.2; // 20% of implementation per year

  const handleCalculate = () => {
    setCalculated(true);
  };

  const handleSaveLead = async (e) => {
    e.preventDefault();
    try {
      await fetch(SAVE_WIZARD_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          session_id: 'business_case_' + Date.now(),
          email: leadData.email,
          telefono: leadData.telefono,
          organizacion: 'Business Case Lead'
        })
      });
      setLeadSaved(true);
    } catch (e) {
      console.error(e);
    }
  };

  const IMPLEMENTATION_COST = Math.max(40000, costBase * 0.05);

  const annualSavings = costBase * (addressablePercentage / 100) * (efficiencyGain / 100);
  const newRevenue = revenue * (efficiencyGain / 100 * 0.025); // very conservative uplift
  const totalBenefit = annualSavings + newRevenue;
  
  // 3-Year Projection with Year 1 Ramp (50%)
  const tco3Year = IMPLEMENTATION_COST + (IMPLEMENTATION_COST * MAINTENANCE_PERCENTAGE * 3);
  const benefit3Year = totalBenefit * 2.5; // Yr1: 0.5, Yr2: 1, Yr3: 1
  
  const roi = ((benefit3Year - tco3Year) / tco3Year) * 100;
  const paybackMonths = IMPLEMENTATION_COST / (totalBenefit / 12); // Based on steady-state

  return (
    <div className="max-w-4xl mx-auto bg-darker rounded-2xl border border-white/10 overflow-hidden shadow-2xl">
      <div className="bg-brand/20 p-6 border-b border-brandCyan/20">
        <h2 className="text-2xl font-bold text-white mb-2"><i className="ph ph-calculator text-brandCyan mr-2"></i> Business Case Estimator</h2>
        <p className="text-gray-300 text-sm">Descubra el impacto potencial de modernizar su arquitectura de datos. Ajuste sus parámetros actuales para generar una proyección instantánea.</p>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2">
        <div className="p-6 border-r border-white/10">
          <h3 className="text-lg font-bold text-white mb-4">Parámetros Operativos</h3>
          
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-400 mb-1">Ingresos Anuales Estimados (USD)</label>
              <input 
                type="number" 
                value={revenue}
                onChange={e => setRevenue(Number(e.target.value))}
                className="w-full bg-black/50 border border-white/20 rounded-lg p-3 text-white focus:outline-none focus:border-brandCyan"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-400 mb-1">Base de Costos Operativos (USD)</label>
              <input 
                type="number" 
                value={costBase}
                onChange={e => setCostBase(Number(e.target.value))}
                className="w-full bg-black/50 border border-white/20 rounded-lg p-3 text-white focus:outline-none focus:border-brandCyan"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-400 mb-1">Expectativa de Eficiencia AI (%)</label>
              <input 
                type="range" 
                min="5" max="40" 
                value={efficiencyGain}
                onChange={e => setEfficiencyGain(Number(e.target.value))}
                className="w-full accent-brandCyan"
              />
              <div className="text-right text-brandCyan font-bold text-sm">{efficiencyGain}%</div>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-400 mb-1">Costos Direccionables por IA (%)</label>
              <input 
                type="range" 
                min="10" max="50" step="5"
                value={addressablePercentage}
                onChange={e => setAddressablePercentage(Number(e.target.value))}
                className="w-full accent-brandCyan"
              />
              <div className="text-right text-brandCyan font-bold text-sm">{addressablePercentage}%</div>
            </div>
            
            <button 
              onClick={handleCalculate}
              className="w-full bg-brand/20 border border-brand/30 text-brandCyan font-bold py-3 rounded-lg hover:bg-brand/30 transition-colors mt-2"
            >
              Proyectar Escenario
            </button>
          </div>
          
          <div className="mt-6 p-4 bg-brand/5 border border-brandCyan/20 rounded-lg text-xs text-gray-400">
            <strong className="text-brandCyan">Supuestos del Modelo:</strong>
            <ul className="list-disc pl-4 mt-2 space-y-1">
              <li>Horizonte de cálculo: TCO y Retorno a 3 años</li>
              <li>Curva de adopción: Beneficio al 50% en Año 1 (Ramp)</li>
              <li>Costo de implementación: Escalonado según tamaño (Mín. $40k)</li>
              <li>Mantenimiento anual: 20% del costo de implementación</li>
              <li>Uplift de ingresos: Muy conservador (Fracción de eficiencia)</li>
            </ul>
          </div>
        </div>
        
        <div className="p-6 bg-black/40">
          <h3 className="text-lg font-bold text-white mb-4">Resultados Proyectados <span className="bg-brandCyan/20 text-brandCyan text-xs px-2 py-1 rounded ml-2">[EST] Escenario Ilustrativo</span></h3>
          
          {!calculated ? (
            <div className="h-full flex items-center justify-center text-gray-500 pb-12">
              <div className="text-center">
                <i className="ph ph-chart-line-up text-4xl mb-2"></i>
                <p>Ajuste los parámetros para calcular</p>
              </div>
            </div>
          ) : (
            <div className="space-y-6 animate-in fade-in">
              <div className="grid grid-cols-2 gap-4">
                <div className="bg-darker p-4 rounded-xl border border-white/5">
                  <div className="text-sm text-gray-400 mb-1">Ahorro Operativo Anual</div>
                  <div className="text-2xl font-bold text-white">${annualSavings.toLocaleString()}</div>
                </div>
                <div className="bg-darker p-4 rounded-xl border border-white/5">
                  <div className="text-sm text-gray-400 mb-1">Impacto Total (EBITDA)</div>
                  <div className="text-2xl font-bold text-brandCyan">+${totalBenefit.toLocaleString()}</div>
                </div>
              </div>
              
              <div className="grid grid-cols-2 gap-4">
                <div className="bg-darker p-4 rounded-xl border border-white/5">
                  <div className="text-sm text-gray-400 mb-1">ROI Proyectado (3 Años)</div>
                  <div className="text-2xl font-bold text-white">
                    {roi > 0 ? (roi > 1000 ? '>1000%' : `${roi.toFixed(0)}%`) : 'N/A (ROI Negativo)'}
                  </div>
                </div>
                <div className="bg-darker p-4 rounded-xl border border-white/5">
                  <div className="text-sm text-gray-400 mb-1">Payback Period</div>
                  <div className="text-2xl font-bold text-brandCyan">
                    {roi > 0 ? `${paybackMonths.toFixed(1)} meses` : '> 36 meses'}
                  </div>
                </div>
              </div>

              <div className="bg-brand/10 p-5 rounded-xl border border-brandCyan/30 mt-6">
                {!leadSaved ? (
                  <form onSubmit={handleSaveLead}>
                    <h4 className="font-bold text-white mb-2">¿Desea una evaluación técnica personalizada?</h4>
                    <p className="text-xs text-gray-300 mb-4">Un arquitecto de soluciones revisará estos números con sus datos reales. Sin compromiso.</p>
                    <div className="space-y-2 mb-3">
                      <input 
                        type="email" 
                        required
                        value={leadData.email}
                        onChange={e => setLeadData({...leadData, email: e.target.value})}
                        placeholder="Correo corporativo" 
                        className="w-full bg-black border border-white/20 rounded p-2 text-sm text-white focus:border-brandCyan outline-none"
                      />
                      <input 
                        type="tel" 
                        value={leadData.telefono}
                        onChange={e => setLeadData({...leadData, telefono: e.target.value})}
                        placeholder="Teléfono (Opcional)" 
                        className="w-full bg-black border border-white/20 rounded p-2 text-sm text-white focus:border-brandCyan outline-none"
                      />
                    </div>
                    <button type="submit" className="w-full bg-brandCyan text-black font-bold py-2 rounded hover:bg-cyan-600 transition-colors text-sm">
                      Solicitar Evaluación
                    </button>
                  </form>
                ) : (
                  <div className="text-center py-4 text-brandCyan">
                    <i className="ph ph-check-circle text-4xl mb-2"></i>
                    <p className="font-bold">Solicitud enviada</p>
                    <p className="text-xs text-gray-300 mt-1">Nos contactaremos a la brevedad.</p>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
