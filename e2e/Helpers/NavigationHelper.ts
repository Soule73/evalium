import { Page } from '@playwright/test';
import { Core } from './Core';

/**
 * Navigation helper for E2E tests
 */
export class NavigationHelper extends Core {
    constructor(page: Page) {
        super(page);
    }

    /**
     * Navigate to dashboard
     */
    async gotoDashboard(): Promise<void> {
        await this.goto('/dashboard');
    }

    /**
     * Navigate to exams list
     */
    async gotoExams(): Promise<void> {
        await this.goto('/exams');
    }

    /**
     * Navigate to create exam
     */
    async gotoCreateExam(): Promise<void> {
        await this.goto('/exams/create');
    }

    /**
     * Navigate to exam details
     */
    async gotoExam(examId: number): Promise<void> {
        await this.goto(`/exams/${examId}`);
    }

    /**
     * Navigate to users management
     */
    async gotoUsers(): Promise<void> {
        await this.goto('/users');
    }

    /**
     * Navigate to roles management
     */
    async gotoRoles(): Promise<void> {
        await this.goto('/roles');
    }

    /**
     * Navigate to groups management
     */
    async gotoGroups(): Promise<void> {
        await this.goto('/groups');
    }

    /**
     * Navigate to student exams (assignments)
     */
    async gotoStudentExams(): Promise<void> {
        await this.goto('/student/exams');
    }

    /**
     * Navigate to student exam details
     */
    async gotoStudentExam(assignmentId: number): Promise<void> {
        await this.goto(`/student/exams/${assignmentId}`);
    }

    /**
     * Navigate to take exam
     */
    async gotoTakeExam(assignmentId: number): Promise<void> {
        await this.goto(`/student/exams/${assignmentId}/take`);
    }

    /**
     * Click on sidebar menu item by test ID
     */
    async clickSidebarItem(testId: string): Promise<void> {
        await this.clickByTestId(`sidebar-${testId}`);
        await this.waitForNavigation();
    }

    /**
     * Go back in browser history
     */
    async goBack(): Promise<void> {
        await this.page.goBack({ waitUntil: 'networkidle' });
    }

    /**
     * Go forward in browser history
     */
    async goForward(): Promise<void> {
        await this.page.goForward({ waitUntil: 'networkidle' });
    }
}
