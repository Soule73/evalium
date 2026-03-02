import { route } from 'ziggy-js';
import type { Assessment } from '@/types/models/assessment';
import type { AssessmentRouteContext } from '@/types/route-context';

/**
 * Resolves the back URL for assessment-related pages (Review, Grade, etc.).
 *
 * Priority order:
 * 1. Direct assessment show route (if available)
 * 2. Class-scoped assessment show route (if classId is resolvable)
 * 3. Fallback back route
 *
 * @param routeContext - Route context passed from the backend
 * @param assessment - The current assessment
 * @returns Resolved absolute URL string
 */
export function getAssessmentBackUrl(
    routeContext: AssessmentRouteContext,
    assessment: Assessment,
): string {
    if (routeContext.showRoute) {
        return route(routeContext.showRoute, assessment.id);
    }
    const classId = assessment.class_subject?.class?.id;
    if (classId && routeContext.classAssessmentShowRoute) {
        return route(routeContext.classAssessmentShowRoute, {
            class: classId,
            assessment: assessment.id,
        });
    }
    return route(routeContext.backRoute);
}
