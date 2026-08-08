# Data Model: Taxonomy Corpus

Estructura del archivo `src/data/taxonomyCorpus.json` derivado de la Spec 003 y orquestado por Spec 004.

```json
[
  {
    "id": "ai-data-science",
    "name": "Inteligencia Artificial & Data Science",
    "slug": "ai-data-science",
    "seo": {
      "title": "Consultoría IA y Data Science B2B | Datanestiq",
      "description": "Modelos predictivos, Machine Learning y arquitecturas GenAI para corporaciones."
    },
    "hero": {
      "headline": "Transforma Terabytes en Ventaja Asimétrica",
      "subheadline": "Desplegamos sistemas cognitivos y LLMs privados que elevan la productividad corporativa de forma radical."
    },
    "contrast": {
      "problem": "Toma de decisiones reactiva basada en intuición y reportes atrasados.",
      "solution": "Forecasting algorítmico e IA Generativa que predicen el mercado antes de que ocurra."
    },
    "features": [
      "Machine Learning y LLMOps",
      "Arquitecturas RAG Corporativas",
      "Sistemas Multi-Agente B2B"
    ]
  }
]
```

## Schema.org (JSON-LD)
Este JSON alimentará un componente estructurado validado por los motores de búsqueda:
```json
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "Inteligencia Artificial & Data Science",
  "provider": {
    "@type": "Organization",
    "name": "Datanestiq"
  },
  "description": "Modelos predictivos, Machine Learning y arquitecturas GenAI."
}
```
