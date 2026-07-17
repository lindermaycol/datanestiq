import React, { useState } from 'react';
import { semanticHighlight } from '../../store/index';
import personas from '../../data/personas.json';
import sectorsCorpus from '../../data/sectorsCorpus.json';
import taxonomyCorpus from '../../data/taxonomyCorpus.json';

export default function SolutionsByRoleAndIndustry() {
    const [activeTab, setActiveTab] = useState('role'); // 'role' | 'industry'
    const [selectedRole, setSelectedRole] = useState(null);

    const resetServiceCards = () => {
        const cards = document.querySelectorAll('.service-card');
        cards.forEach(card => {
            card.style.opacity = '1';
            card.style.transform = 'none';
            card.style.borderColor = '';
            card.style.boxShadow = '';
        });
    };

    const handleRoleClick = (role) => {
        setSelectedRole(role);
        
        // Scroll to services
        const servicesSection = document.getElementById('services');
        if (servicesSection) {
            servicesSection.scrollIntoView({ behavior: 'smooth' });
        }

        // Resaltar por DOM las tarjetas de servicio de interés, atenuar el resto
        const interest = new Set(role.pillarsOfInterest); // slugs de pilar
        const cards = document.querySelectorAll('.service-card');
        cards.forEach(card => {
            const slug = card.getAttribute('data-service-id');
            if (interest.has(slug)) {
                card.style.opacity = '1';
                card.style.transform = 'scale(1.02)';
                card.style.borderColor = 'var(--brand-cyan)';
                card.style.boxShadow = '0 0 20px rgba(0, 240, 255, 0.1)';
            } else {
                card.style.opacity = '0.3';
                card.style.transform = 'scale(0.98)';
                card.style.borderColor = 'rgba(255,255,255,0.1)';
                card.style.boxShadow = 'none';
            }
        });
    };

    const handleIndustryClick = (sectorId) => {
        if (sectorId === 'all') {
            const searchInput = document.querySelector('input[placeholder*="Ej: Tengo problemas"]');
            if (searchInput) {
                searchInput.focus();
                window.scrollTo({ top: searchInput.offsetTop - 100, behavior: 'smooth' });
            }
            return;
        }

        const sector = sectorsCorpus.find(s => s.id === sectorId);
        if (sector && sector.slug) {
            window.location.href = `/sectores/${sector.slug}`;
        }
    };

    const switchTab = (tab) => {
        setActiveTab(tab);
        setSelectedRole(null);
        resetServiceCards();
    };

    return (
        <div className="max-w-5xl mx-auto py-12 px-4">
            <div className="flex justify-center mb-8">
                <div className="inline-flex bg-darker p-1 rounded-xl border border-white/10">
                    <button 
                        onClick={() => switchTab('role')}
                        className={`px-6 py-2 rounded-lg text-sm font-medium transition-all ${activeTab === 'role' ? 'bg-brand/20 text-brandCyan' : 'text-gray-400 hover:text-white'}`}
                    >
                        Soluciones por Rol
                    </button>
                    <button 
                        onClick={() => switchTab('industry')}
                        className={`px-6 py-2 rounded-lg text-sm font-medium transition-all ${activeTab === 'industry' ? 'bg-brand/20 text-brandCyan' : 'text-gray-400 hover:text-white'}`}
                    >
                        Soluciones por Industria
                    </button>
                </div>
            </div>

            {activeTab === 'role' && (
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    {personas.roles.map(role => (
                        <button 
                            key={role.id}
                            onClick={() => handleRoleClick(role)}
                            className="glass-card p-4 text-left hover:border-brandCyan/50 transition-colors group relative"
                        >
                            <h3 className="text-white font-semibold text-sm mb-1">{role.title}</h3>
                            <p className="text-xs text-gray-400">Meta: {role.goals[0]}</p>
                            <i className="ph ph-arrow-right absolute right-4 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 text-brandCyan transition-opacity"></i>
                        </button>
                    ))}
                    {selectedRole && (
                        <div className="col-span-2 md:col-span-4 mt-4 p-4 bg-brand/10 border border-brandCyan/20 rounded-xl text-sm flex flex-col gap-2">
                            <div>
                                <span className="text-brandCyan font-semibold">Resaltando soluciones para {selectedRole.title}:</span> Enfocadas en mitigar "{selectedRole.objections ? selectedRole.objections[0] : selectedRole.pains[0]}" priorizando "{selectedRole.decisionCriteria ? selectedRole.decisionCriteria[0] : selectedRole.goals[0]}".
                            </div>
                            {selectedRole.relevantSectors && selectedRole.relevantSectors.length > 0 && (
                                <div className="text-gray-400 text-xs">
                                    <strong className="text-gray-300">Sectores de alto impacto:</strong> {selectedRole.relevantSectors.map(s => sectorsCorpus.find(sc => sc.id === s)?.title).filter(Boolean).join(', ')}
                                </div>
                            )}
                            {selectedRole.pillarsOfInterest && selectedRole.pillarsOfInterest.length > 0 && (
                                (() => {
                                    const pillar = taxonomyCorpus.find(p => p.slug === selectedRole.pillarsOfInterest[0]);
                                    if (pillar && pillar.proofPoints && pillar.proofPoints.length > 0) {
                                        return (
                                            <div className="text-gray-400 text-xs mt-1 border-l-2 border-brandCyan pl-2">
                                                <strong className="text-brandCyan">Ejemplo de Valor ({pillar.name}):</strong> {pillar.proofPoints[0].client} - {pillar.proofPoints[0].result}
                                            </div>
                                        );
                                    }
                                    return null;
                                })()
                            )}
                        </div>
                    )}
                </div>
            )}

            {activeTab === 'industry' && (
                <div>
                    <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                        {sectorsCorpus.map(sector => (
                            <button 
                                key={sector.id}
                                onClick={() => handleIndustryClick(sector.id)}
                                className="glass-card p-3 text-center hover:border-brandCyan/50 transition-colors"
                            >
                                <i className={`ph ${sector.icon} text-2xl text-brandCyan mb-2`}></i>
                                <h3 className="text-white text-xs font-medium">{sector.title}</h3>
                            </button>
                        ))}
                    </div>
                    <div className="text-center mt-6">
                        <button onClick={() => handleIndustryClick('all')} className="text-brandCyan text-sm font-medium hover:underline flex items-center justify-center gap-1 mx-auto">
                            Buscar industrias adicionales <i className="ph ph-magnifying-glass"></i>
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
