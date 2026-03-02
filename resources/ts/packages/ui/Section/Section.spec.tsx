import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import Section from './Section';

describe('Section Component', () => {
    it('renders with title and children', () => {
        render(
            <Section title="Test Section">
                <div>Section content</div>
            </Section>,
        );

        expect(screen.getByText('Test Section')).toBeInTheDocument();
        expect(screen.getByText('Section content')).toBeInTheDocument();
    });

    it('renders with subtitle', () => {
        render(
            <Section title="Test Section" subtitle="Test subtitle">
                <div>Content</div>
            </Section>,
        );

        expect(screen.getByText('Test Section')).toBeInTheDocument();
        expect(screen.getByText('Test subtitle')).toBeInTheDocument();
    });

    it('renders with actions', () => {
        render(
            <Section title="Test Section" actions={<button>Action Button</button>}>
                <div>Content</div>
            </Section>,
        );

        expect(screen.getByText('Action Button')).toBeInTheDocument();
    });

    it('renders with custom className', () => {
        const { container } = render(
            <Section title="Test Section" className="custom-class">
                <div>Content</div>
            </Section>,
        );

        const section = container.querySelector('.custom-class');
        expect(section).toBeInTheDocument();
    });

    it('applies base styles correctly', () => {
        const { container } = render(
            <Section title="Test Section">
                <div>Content</div>
            </Section>,
        );

        const section = container.firstChild;
        expect(section).toBeInTheDocument();
    });

    it('renders multiple children correctly', () => {
        render(
            <Section title="Test Section">
                <div>First child</div>
                <div>Second child</div>
            </Section>,
        );

        expect(screen.getByText('First child')).toBeInTheDocument();
        expect(screen.getByText('Second child')).toBeInTheDocument();
    });
});
