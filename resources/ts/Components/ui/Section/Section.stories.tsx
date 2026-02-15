import type { Meta, StoryObj } from "@storybook/react";
import Section from "./Section";
import Button from "../Button/Button";

const meta = {
    title: "UI/Section",
    component: Section,
    parameters: {
        layout: "padded",
    },

    argTypes: {
        collapsible: {
            control: "boolean",
        },
        defaultOpen: {
            control: "boolean",
        },
        variant: {
            control: "select",
            options: ["elevated", "flat"],
        },
    },
} satisfies Meta<typeof Section>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
    args: {
        title: "Section Title",
        children: (
            <div className="space-y-4">
                <p className="text-gray-600">This is the section content.</p>
                <p className="text-gray-600">
                    It can contain any React components or HTML elements.
                </p>
            </div>
        ),
    },
};

export const WithSubtitle: Story = {
    args: {
        title: "User Settings",
        subtitle: "Manage your account preferences and settings",
        children: (
            <div className="space-y-4">
                <p className="text-gray-600">
                    Configure your profile information and preferences.
                </p>
            </div>
        ),
    },
};

export const WithActions: Story = {
    args: {
        title: "Assessment Management",
        subtitle: "Create and manage your assessments",
        actions: (
            <div className="flex space-x-2">
                <Button size="sm" variant="outline" color="secondary">
                    Cancel
                </Button>
                <Button size="sm" color="primary">
                    Create Assessment
                </Button>
            </div>
        ),
        children: (
            <div className="space-y-4">
                <p className="text-gray-600">
                    List of all assessments will appear here.
                </p>
            </div>
        ),
    },
};

export const Collapsible: Story = {
    args: {
        title: "Advanced Settings",
        subtitle: "Optional configuration options",
        collapsible: true,
        defaultOpen: true,
        children: (
            <div className="space-y-4">
                <p className="text-gray-600">
                    These settings are optional and can be collapsed.
                </p>
                <div className="grid grid-cols-2 gap-4">
                    <div className="p-4 bg-gray-50 rounded">Setting 1</div>
                    <div className="p-4 bg-gray-50 rounded">Setting 2</div>
                    <div className="p-4 bg-gray-50 rounded">Setting 3</div>
                    <div className="p-4 bg-gray-50 rounded">Setting 4</div>
                </div>
            </div>
        ),
    },
};

export const CollapsibleClosed: Story = {
    args: {
        title: "Additional Information",
        collapsible: true,
        defaultOpen: false,
        children: (
            <div className="space-y-4">
                <p className="text-gray-600">This section starts collapsed.</p>
            </div>
        ),
    },
};

export const WithCustomTitle: Story = {
    args: {
        title: (
            <div className="flex items-center space-x-2">
                <svg
                    className="w-5 h-5 text-indigo-500"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M13 10V3L4 14h7v7l9-11h-7z"
                    />
                </svg>
                <span>Custom Title with Icon</span>
            </div>
        ),
        children: (
            <div className="space-y-4">
                <p className="text-gray-600">
                    The title can be any React node.
                </p>
            </div>
        ),
    },
};

export const Flat: Story = {
    args: {
        title: "Flat Section",
        subtitle: "A section without background and shadow",
        variant: "flat",
        actions: (
            <Button size="sm" color="primary">
                Action
            </Button>
        ),
        children: (
            <div className="space-y-4">
                <p className="text-gray-600">
                    This section has a flat variant without elevation.
                </p>
            </div>
        ),
    },
};

export const WithRichContent: Story = {
    args: {
        title: "Assessment Questions",
        subtitle: "5 questions total",
        actions: (
            <Button size="sm" color="primary">
                Add Question
            </Button>
        ),
        children: (
            <div className="space-y-3">
                {[1, 2, 3, 4, 5].map((num) => (
                    <div
                        key={num}
                        className="p-4 bg-gray-50 rounded-lg border border-gray-200"
                    >
                        <h4 className="font-medium text-gray-900 mb-2">
                            Question {num}
                        </h4>
                        <p className="text-sm text-gray-600">
                            This is a sample question content.
                        </p>
                    </div>
                ))}
            </div>
        ),
    },
};
