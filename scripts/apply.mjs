import fs from 'fs';
import path from 'path';
import { load, dump } from 'js-yaml';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const ROOT_DIR = path.join(__dirname, '..');
const SECTORS_DIR = path.join(ROOT_DIR, 'src/content/sectors');
const PILLARS_DIR = path.join(ROOT_DIR, 'src/content/pillars');
const PERSONAS_FILE = path.join(ROOT_DIR, 'src/data/personas.json');

const sectorsData = {
  'finanzas.yaml': {
    subSectors: ['Banca Minorista', 'Banca Corporativa y de Inversión', 'Fintech'],
    kpis: [
      { metric: 'Reducción de falsos positivos en fraude', expectedRoi: '[EST] 45% de mejora' },
      { metric: 'Tiempo de evaluación crediticia', expectedRoi: '[EST] De 3 días a 20 minutos' }
    ],
    regulations: ['Basilea III / IV', 'PCI-DSS', 'AML-KYC', 'SBS (Perú)'],
    relevantPersonas: ['cfo', 'ciso', 'cdo'],
    contentAngles: [
      {
        id: 'fraude-tiempo-real-banca',
        title: 'Detección de Fraude en Tiempo Real',
        brief: 'Artículo de 1200 palabras para el CFO sobre cómo la IA predictiva y el streaming de datos reducen las pérdidas por fraude en un 45% cumpliendo con normativas PCI-DSS y AML.'
      }
    ]
  },
  'salud.yaml': {
    subSectors: ['Hospitales y Clínicas', 'Redes de Salud Integradas', 'Seguros de Salud'],
    kpis: [
      { metric: 'Reducción de readmisiones', expectedRoi: '[EST] 15% de reducción' },
      { metric: 'Optimización de camas hospitalarias', expectedRoi: '[EST] 20% más disponibilidad' }
    ],
    regulations: ['HIPAA', 'HL7-FHIR', 'Ley de Protección de Datos Personales'],
    relevantPersonas: ['coo', 'cio', 'cdo'],
    contentAngles: [
      {
        id: 'interoperabilidad-salud-fhir',
        title: 'Interoperabilidad Clínica con HL7-FHIR',
        brief: 'Guía técnica para el CIO sobre cómo unificar historias clínicas dispersas en un Data Lake seguro cumpliendo con normativas HIPAA y protocolos FHIR.'
      }
    ]
  },
  'retail.yaml': {
    subSectors: ['E-commerce', 'Supermercados', 'Moda y Apparel'],
    kpis: [
      { metric: 'Reducción de quiebres de stock', expectedRoi: '[EST] -30%' },
      { metric: 'Aumento del ticket promedio', expectedRoi: '[EST] +12%' }
    ],
    regulations: ['Ley del Consumidor', 'PCI-DSS', 'Leyes de Privacidad (Cookies)'],
    relevantPersonas: ['coo', 'ceo', 'cdo'],
    contentAngles: [
      {
        id: 'forecast-demanda-retail',
        title: 'Predicción de Demanda con Machine Learning',
        brief: 'Artículo para el COO detallando cómo los algoritmos de predicción de demanda reducen los quiebres de stock y optimizan el capital de trabajo en el retail.'
      }
    ]
  },
  'manufactura.yaml': {
    subSectors: ['Automotriz', 'Consumo Masivo', 'Maquinaria Pesada'],
    kpis: [
      { metric: 'Mejora del OEE (Eficiencia General)', expectedRoi: '[EST] +20%' },
      { metric: 'Costos de mantenimiento no planificado', expectedRoi: '[EST] -25%' }
    ],
    regulations: ['ISO 9001', 'OHSAS 18001 / ISO 45001', 'Normativas Ambientales'],
    relevantPersonas: ['coo', 'cto'],
    contentAngles: [
      {
        id: 'mantenimiento-predictivo-iot',
        title: 'Mantenimiento Predictivo con IoT e IA',
        brief: 'Caso de uso dirigido al Director de Operaciones (COO) sobre cómo procesar datos de sensores IoT en tiempo real previene paradas de planta millonarias.'
      }
    ]
  },
  'logistica.yaml': {
    subSectors: ['Última Milla', 'Transporte Internacional de Carga', 'Centros de Distribución'],
    kpis: [
      { metric: 'Costos de combustible', expectedRoi: '[EST] -15%' },
      { metric: 'Entregas a tiempo (OTIF)', expectedRoi: '[EST] Mejora a 98%' }
    ],
    regulations: ['Regulaciones de Aduanas', 'Normativas de Emisiones (ESG)'],
    relevantPersonas: ['coo', 'ceo', 'cto'],
    contentAngles: [
      {
        id: 'ruteo-dinamico-ia',
        title: 'Ruteo Dinámico y Torres de Control AI',
        brief: 'Artículo explicando cómo una arquitectura de datos en tiempo real y algoritmos de ruteo dinámico ahorran un 15% en costos logísticos.'
      }
    ]
  },
  'educacion.yaml': {
    subSectors: ['Universidades', 'EdTech', 'Institutos Técnicos'],
    kpis: [
      { metric: 'Reducción de deserción estudiantil', expectedRoi: '[EST] -20%' },
      { metric: 'Incremento de matrícula', expectedRoi: '[EST] +15% de conversión' }
    ],
    regulations: ['FERPA', 'Ley Universitaria', 'Privacidad de Menores'],
    relevantPersonas: ['ceo', 'cio', 'cdo'],
    contentAngles: [
      {
        id: 'prediccion-desercion-estudiantil',
        title: 'Evitando la Deserción Estudiantil con Analítica',
        brief: 'Reporte para el equipo directivo sobre el uso de modelos predictivos que identifican estudiantes en riesgo antes de que abandonen la institución.'
      }
    ]
  },
  'mineria.yaml': {
    subSectors: ['Extracción', 'Procesamiento de Minerales'],
    kpis: [
      { metric: 'Reducción de incidentes de seguridad', expectedRoi: '[EST] -40%' },
      { metric: 'Disponibilidad de maquinaria', expectedRoi: '[EST] +15%' }
    ],
    regulations: ['Normas Mineras Nacionales', 'Estándares ESG', 'Regulaciones Ambientales'],
    relevantPersonas: ['coo', 'cfo', 'ceo'],
    contentAngles: [
      {
        id: 'gemelos-digitales-mineria',
        title: 'Gemelos Digitales en la Minería',
        brief: 'Artículo técnico-estratégico sobre cómo los Digital Twins y la simulación optimizan la extracción y elevan la seguridad en minas a tajo abierto.'
      }
    ]
  },
  'seguros.yaml': {
    subSectors: ['Seguros de Vida', 'Seguros Generales y Vehiculares', 'Insurtech'],
    kpis: [
      { metric: 'Tiempo de resolución de siniestros', expectedRoi: '[EST] de 15 días a 24 horas' },
      { metric: 'Detección de reclamos fraudulentos', expectedRoi: '[EST] +35% de precisión' }
    ],
    regulations: ['Solvencia II', 'Superintendencia de Seguros (SBS)'],
    relevantPersonas: ['cfo', 'cdo', 'cio'],
    contentAngles: [
      {
        id: 'procesamiento-siniestros-ia',
        title: 'Hiperautomatización de Siniestros',
        brief: 'Guía para líderes de Insurtech detallando el procesamiento inteligente de documentos (IDP) para validar siniestros en tiempo récord.'
      }
    ]
  },
  'telecomunicaciones.yaml': {
    subSectors: ['ISPs', 'Operadores Móviles', 'Fibra Óptica'],
    kpis: [
      { metric: 'Reducción de Churn', expectedRoi: '[EST] -25%' },
      { metric: 'Optimización de red y CAPEX', expectedRoi: '[EST] 15% de ahorro' }
    ],
    regulations: ['Ley General de Telecomunicaciones', 'Protección de Datos', 'Neutralidad de Red'],
    relevantPersonas: ['ceo', 'cto', 'cdo'],
    contentAngles: [
      {
        id: 'prevencion-churn-telecom',
        title: 'Machine Learning para Prevenir el Churn',
        brief: 'Análisis para directores de telecomunicaciones sobre cómo predecir la fuga de clientes procesando billones de CDRs (Call Detail Records) diarios.'
      }
    ]
  }
};

const pillarsData = {
  'business-intelligence.yaml': {
    techStack: ['Power BI', 'Tableau', 'Looker', 'dbt'],
    proofPoints: [
      { client: 'Cadena de Retail Top 5', result: '[EST] 30% ahorro en horas-hombre para reportes' },
      { client: 'Empresa Logística Internacional', result: '[EST] Visibilidad 100% en tiempo real de márgenes operativos' }
    ],
    competitivePositioning: 'Implementamos Dashboards Self-Service con integración nativa a Lakehouses, liberando a las empresas de licenciamientos cautivos y pesados de BI tradicional (SAP, Oracle).',
    contentAngles: [
      {
        id: 'fin-de-reportes-estaticos',
        title: 'El Fin de los Reportes Estáticos',
        brief: 'Manifiesto para el CFO: por qué depender de Excel y reportes mensuales destruye el margen, y cómo el BI en tiempo real cambia las reglas.'
      }
    ]
  },
  'data-engineering.yaml': {
    techStack: ['Snowflake', 'Databricks', 'Apache Spark', 'Kafka', 'Iceberg'],
    proofPoints: [
      { client: 'Banco Regional', result: '[EST] 10x velocidad en pipelines de datos nocturnos' }
    ],
    competitivePositioning: 'Construimos Arquitecturas Lakehouse con open-formats (Apache Iceberg, Delta) que previenen el vendor lock-in a diferencia de ecosistemas cerrados y costosos de legacy data warehouses.',
    contentAngles: [
      {
        id: 'data-lake-vs-data-warehouse-cdo',
        title: 'Lakehouse: Lo mejor de ambos mundos',
        brief: 'Explicación técnica para el CDO sobre cómo una arquitectura Data Lakehouse resuelve el problema de silos sin los costos inflexibles de un Data Warehouse tradicional.'
      }
    ]
  },
  'estrategia-datos-ia.yaml': {
    techStack: ['Frameworks DAMA-DMBOK', 'Metodologías Ágiles', 'Data Mesh'],
    proofPoints: [
      { client: 'Gobierno Regional', result: '[EST] 100% de cumplimiento en normativas de transparencia en menos de un año' }
    ],
    competitivePositioning: 'Consultoría completamente agnóstica orientada al ROI financiero, no a forzar la venta de licencias de software, alineando la IA con la estrategia real del directorio.',
    contentAngles: [
      {
        id: 'estrategia-datos-ceo',
        title: 'Los Datos como Activo Financiero',
        brief: 'Mensaje para el CEO: cómo calcular el valor contable de los datos de su empresa y por qué la gobernanza no es un tema técnico, sino de negocio.'
      }
    ]
  },
  'hiperautomatizacion.yaml': {
    techStack: ['UiPath', 'Automation Anywhere', 'Python RPA', 'Document AI'],
    proofPoints: [
      { client: 'Aseguradora Multinacional', result: '[EST] 70% de tareas repetitivas en back-office automatizadas' }
    ],
    competitivePositioning: 'No implementamos scripts frágiles de RPA que se rompen con cada actualización; usamos Inteligencia Artificial (IDP y LLMs) para crear automatización cognitiva que maneja excepciones.',
    contentAngles: [
      {
        id: 'hiperautomatizacion-backoffice-coo',
        title: 'Hiperautomatización del Back-Office',
        brief: 'Guía para el COO sobre cómo escalar las operaciones y absorber 10x más volumen de trabajo sin escalar linealmente la planilla, usando RPA cognitivo.'
      }
    ]
  },
  'sistemas-digitales.yaml': {
    techStack: ['React', 'Astro', 'Node.js', 'AWS', 'Docker / Kubernetes'],
    proofPoints: [
      { client: 'Startup Fintech', result: '[EST] 50% de mejora en el Time-To-Market de su aplicación core' }
    ],
    competitivePositioning: 'Desarrollo cloud-native de alto rendimiento con deuda técnica cero. Escribimos código limpio y escalable frente a fábricas de software genéricas que priorizan el volumen.',
    contentAngles: [
      {
        id: 'deuda-tecnica-cto',
        title: 'El Costo Oculto de la Deuda Técnica',
        brief: 'Análisis para el CTO evaluando financieramente el peso del legacy code y cómo una reestructuración a arquitecturas modernas (microservicios/Jamstack) libera presupuesto para innovación.'
      }
    ]
  }
};

const personasData = {
  'cfo': {
    buyerRole: 'economic',
    objections: ['El ROI de proyectos de datos suele ser abstracto o a muy largo plazo', 'Reemplazar el ERP cuesta demasiado'],
    decisionCriteria: ['Payback Period y TCO', 'Previsibilidad presupuestaria', 'Impacto directo en el EBITDA'],
    triggers: ['Cierres contables que toman semanas', 'Desviaciones graves no anticipadas en el P&L'],
    relevantSectors: ['sector-finanzas', 'sector-seguros'],
    contentAngles: [
      {
        id: 'roi-data-analytics-cfo',
        title: 'Medición del ROI en Proyectos de Datos',
        brief: 'Cómo estructurar el business case financiero para iniciativas de Data Analytics y asegurar el retorno en menos de 12 meses.'
      }
    ]
  },
  'cdo': {
    buyerRole: 'technical',
    objections: ['Nuestra calidad de datos actual es muy baja para aplicar IA', 'Falta cultura del dato en la empresa'],
    decisionCriteria: ['Escalabilidad de la gobernanza', 'Adopción de usuarios', 'Reducción de silos de datos'],
    triggers: ['Multas regulatorias por mal manejo de datos', 'Múltiples versiones de la verdad en juntas directivas'],
    relevantSectors: ['sector-retail', 'sector-telecomunicaciones', 'sector-publico'],
    contentAngles: [
      {
        id: 'gobierno-datos-cdo',
        title: 'Gobernanza de Datos sin Fricción',
        brief: 'Estrategias para que el CDO implemente políticas de calidad y master data management (MDM) sin convertirse en el cuello de botella del negocio.'
      }
    ]
  },
  'cto': {
    buyerRole: 'technical',
    objections: ['Tenemos demasiada deuda técnica', 'No quiero quedar atrapado en el ecosistema de un solo vendor (Lock-in)'],
    decisionCriteria: ['Flexibilidad de arquitectura', 'Seguridad y Compliance', 'Facilidad de integración mediante APIs'],
    triggers: ['Caídas críticas del sistema', 'Migración inminente a Cloud'],
    relevantSectors: ['sector-manufactura', 'sector-logistica', 'sector-telecomunicaciones'],
    contentAngles: [
      {
        id: 'modernizacion-arquitectura-cto',
        title: 'Modernización de Arquitecturas Críticas',
        brief: 'Ruta para desacoplar monolitos y transicionar a eventos (Kafka) y microservicios sin interrumpir la operación continua.'
      }
    ]
  },
  'coo': {
    buyerRole: 'user',
    objections: ['Mi equipo de operaciones se resistirá a usar un nuevo sistema', 'No podemos detener la producción para implementar'],
    decisionCriteria: ['Aumento de eficiencia (OEE, Tiempos)', 'Facilidad de uso en piso de planta o almacén', 'Soporte 24/7'],
    triggers: ['Cuellos de botella evidentes', 'Aumento de horas extra y errores humanos'],
    relevantSectors: ['sector-manufactura', 'sector-salud', 'sector-retail', 'sector-logistica'],
    contentAngles: [
      {
        id: 'hiperautomatizacion-operaciones-coo',
        title: 'Escalando Operaciones sin Escalar Costos',
        brief: 'Cómo el COO puede apalancar la IA para predecir cuellos de botella y automatizar procesos manuales tediosos, elevando la moral y productividad del equipo.'
      }
    ]
  },
  'ciso': {
    buyerRole: 'technical',
    objections: ['Los modelos de IA pública exponen nuestros datos confidenciales', 'Nuestra superficie de ataque ya es muy amplia'],
    decisionCriteria: ['Cumplimiento normativo (ISO 27001, SOC2)', 'Encriptación End-to-End', 'Despliegues On-Premise/VPC'],
    triggers: ['Auditoría de seguridad fallida', 'Brecha de datos reciente en la industria'],
    relevantSectors: ['sector-finanzas', 'sector-publico', 'sector-salud'],
    contentAngles: [
      {
        id: 'seguridad-datos-ciso',
        title: 'Ciberseguridad en la Era de la GenAI',
        brief: 'Guía táctica para CISOs sobre cómo proteger la propiedad intelectual al implementar grandes modelos de lenguaje, priorizando despliegues privados.'
      }
    ]
  },
  'ceo': {
    buyerRole: 'economic',
    objections: ['La IA es solo una moda técnica, no entiendo cómo impacta mi estrategia', 'Nuestra empresa no es una tecnológica'],
    decisionCriteria: ['Ventaja competitiva y Cuota de Mercado', 'Crecimiento de ingresos', 'Transformación digital del modelo de negocio'],
    triggers: ['Presión disruptiva de competidores nativos digitales', 'Estancamiento en el crecimiento interanual'],
    relevantSectors: ['sector-retail', 'sector-finanzas', 'sector-educacion', 'sector-logistica'],
    contentAngles: [
      {
        id: 'ia-ventaja-competitiva-ceo',
        title: 'La IA como Diferenciador Estratégico',
        brief: 'Por qué delegar la estrategia de IA exclusivamente a IT es un error. Un manifiesto para CEOs sobre liderar la transformación corporativa.'
      }
    ]
  }
};

// Apply Sectors
for (const [file, enrichment] of Object.entries(sectorsData)) {
  const fp = path.join(SECTORS_DIR, file);
  if (fs.existsSync(fp)) {
    const doc = load(fs.readFileSync(fp, 'utf8'));
    Object.assign(doc, enrichment);
    fs.writeFileSync(fp, dump(doc));
    console.log(`Enriched ${file}`);
  }
}

// Apply Pillars
for (const [file, enrichment] of Object.entries(pillarsData)) {
  const fp = path.join(PILLARS_DIR, file);
  if (fs.existsSync(fp)) {
    const doc = load(fs.readFileSync(fp, 'utf8'));
    Object.assign(doc, enrichment);
    fs.writeFileSync(fp, dump(doc));
    console.log(`Enriched ${file}`);
  }
}

// Apply Personas
if (fs.existsSync(PERSONAS_FILE)) {
  const personas = JSON.parse(fs.readFileSync(PERSONAS_FILE, 'utf8'));
  for (const role of personas.roles) {
    if (personasData[role.id]) {
      Object.assign(role, personasData[role.id]);
    }
  }
  fs.writeFileSync(PERSONAS_FILE, JSON.stringify(personas, null, 2));
  console.log(`Enriched personas.json`);
}

console.log("Enrichment complete.");
