import React, { useEffect } from 'react';
import { useStore } from '@nanostores/react';
import { semanticHighlight } from '../../store/index';

export default function HighlightSync() {
    const highlightState = useStore(semanticHighlight);

    useEffect(() => {
        const serviceCards = document.querySelectorAll('.service-card');
        if (!serviceCards.length) return;
        
        const hasHighlights = highlightState && Object.keys(highlightState).length > 0;

        serviceCards.forEach(card => {
            if (!hasHighlights) {
                card.classList.remove('is-highlighted');
                card.classList.remove('is-dimmed');
                // Remove inline styles set by previous bugs if any
                card.style.removeProperty('border-color');
                card.style.removeProperty('box-shadow');
                return;
            }

            const sId = card.getAttribute('data-service-id');
            const score = highlightState[`service-${sId}`] || 0;
            const threshold = 0.2;
            
            if (score >= threshold) {
                card.classList.add('is-highlighted');
                card.classList.remove('is-dimmed');
            } else {
                card.classList.add('is-dimmed');
                card.classList.remove('is-highlighted');
            }
        });
    }, [highlightState]);

    return null; // Componente invisible, solo sincroniza el DOM
}
