import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import Section from '../Section';

describe('Section Component', () => {
    it('renders with title and children', () => {
        render(
            <Section title="Test Section">
                <div>Section content</div>
            </Section>
        );

        expect(screen.getByText('Test Section')).toBeInTheDocument();
        expect(screen.getByText('Section content')).toBeInTheDocument();
    });

    it('renders with subtitle', () => {
        render(
            <Section title="Test Section" subtitle="Test subtitle">
                <div>Content</div>
            </Section>
        );

        expect(screen.getByText('Test Section')).toBeInTheDocument();
        expect(screen.getByText('Test subtitle')).toBeInTheDocument();
    });

    it('renders with actions', () => {
        render(
            <Section title="Test Section" actions={<button>Action Button</button>}>
                <div>Content</div>
            </Section>
        );

        expect(screen.getByText('Action Button')).toBeInTheDocument();
    });

    it('renders with custom className', () => {
        const { container } = render(
            <Section title="Test Section" className="custom-class">
                <div>Content</div>
            </Section>
        );

        const section = container.querySelector('.custom-class');
        expect(section).toBeInTheDocument();
    });

    it('renders collapsible section', () => {
        const { container } = render(
            <Section title="Collapsible Section" collapsible={true}>
                <div>Collapsible content</div>
            </Section>
        );

        expect(screen.getByText('Collapsible Section')).toBeInTheDocument();
        // Le wrapper cliquable devrait être présent
        const clickableWrapper = container.querySelector('.cursor-pointer');
        expect(clickableWrapper).toBeInTheDocument();
    });

    it('renders with defaultOpen prop for collapsible', () => {
        render(
            <Section title="Section" collapsible={true} defaultOpen={false}>
                <div>Hidden content</div>
            </Section>
        );

        // Le contenu ne devrait pas être visible par défaut
        expect(screen.queryByText('Hidden content')).not.toBeInTheDocument();
    });

    it('renders with all props combined', () => {
        const { container } = render(
            <Section
                title="Complete Section"
                subtitle="With all features"
                actions={<button>Action</button>}
                className="complete-section"
                collapsible={true}
                defaultOpen={true}
            >
                <div>Full content</div>
            </Section>
        );

        expect(screen.getByText('Complete Section')).toBeInTheDocument();
        expect(screen.getByText('With all features')).toBeInTheDocument();
        expect(screen.getByText('Action')).toBeInTheDocument();
        expect(screen.getByText('Full content')).toBeInTheDocument();
        expect(container.querySelector('.complete-section')).toBeInTheDocument();
    });
});
