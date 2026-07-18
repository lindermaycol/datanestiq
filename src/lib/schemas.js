import { z } from 'zod';

export const pillarSchema = z.object({
  id: z.string(),
  name: z.string(),
  slug: z.string(),
  maturityStage: z.string().optional(),
  standards: z.array(z.string()).optional(),
  targetRoles: z.array(z.string()).optional(),
  skills: z.array(z.string()).optional(),
  seo: z.object({
    title: z.string(),
    description: z.string(),
  }),
  hero: z.object({
    headline: z.string(),
    subheadline: z.string(),
  }),
  contrast: z.object({
    problem: z.string(),
    solution: z.string(),
  }),
  features: z.array(z.string()),
  keywords: z.array(z.string()),
  copilotPrompts: z.array(z.string()).optional(),
  techStack: z.array(z.string()).optional(),
  proofPoints: z.array(z.object({ client: z.string(), result: z.string() })).optional(),
  competitivePositioning: z.string().optional(),
  contentAngles: z.array(z.object({ id: z.string(), title: z.string(), brief: z.string() })).optional(),
});

export const sectorSchema = z.object({
  id: z.string(),
  slug: z.string(),
  title: z.string(),
  description: z.string(),
  icon: z.string(),
  ciiu: z.string().optional(),
  keywords: z.array(z.string()),
  seo: z.object({
    title: z.string().optional(),
    description: z.string().optional(),
  }).optional(),
  hero: z.object({
    headline: z.string().optional(),
    subheadline: z.string().optional(),
  }).optional(),
  contrast: z.object({
    problem: z.string().optional(),
    solution: z.string().optional(),
  }).optional(),
  problems: z.array(
    z.object({
      code: z.string(),
      label: z.string(),
      solution: z.string(),
    })
  ).optional(),
  subSectors: z.array(z.string()).optional(),
  kpis: z.array(z.object({ metric: z.string(), expectedRoi: z.string() })).optional(),
  regulations: z.array(z.string()).optional(),
  deploymentModels: z.array(z.string()).optional(),
  engagementModels: z.array(z.string()).optional(),
  relevantPersonas: z.array(z.string()).optional(),
  roiCases: z.array(z.object({
    title: z.string(),
    description: z.string(),
    context: z.string().optional(),
    metrics: z.array(z.object({
      value: z.string(),
      label: z.string(),
    })).optional()
  })).optional(),
  institutionalContinuity: z.object({
    title: z.string(),
    description: z.string(),
    features: z.array(z.string()),
  }).optional(),
  contentAngles: z.array(z.object({ id: z.string(), title: z.string(), brief: z.string() })).optional(),
});

export const industrySchema = z.object({
  id: z.string(),
  title: z.string(),
  ciiu: z.string().optional(),
  keywords: z.array(z.string()),
  relatedPillars: z.array(z.string()),
});

export const personaSchema = z.object({
  roles: z.array(
    z.object({
      id: z.string(),
      title: z.string(),
      goals: z.array(z.string()),
      pains: z.array(z.string()),
      pillarsOfInterest: z.array(z.string()),
      buyerRole: z.enum(['economic', 'technical', 'user']).optional(),
      objections: z.array(z.string()).optional(),
      objectionResponses: z.array(z.object({ objection: z.string(), response: z.string() })).optional(),
      decisionCriteria: z.array(z.string()).optional(),
      triggers: z.array(z.string()).optional(),
      relevantSectors: z.array(z.string()).optional(),
      contentAngles: z.array(z.object({ id: z.string(), title: z.string(), brief: z.string() })).optional(),
    })
  ),
  orgTypes: z.array(
    z.object({
      id: z.string(),
      title: z.string(),
    })
  ),
});
