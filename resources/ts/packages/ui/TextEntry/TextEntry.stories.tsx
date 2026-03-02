import type { Meta, StoryObj } from '@storybook/react';
import TextEntry from './TextEntry';
import Badge from '../Badge/Badge';

const meta = {
    title: 'UI/TextEntry',
    component: TextEntry,
    parameters: {
        layout: 'centered',
    },
} satisfies Meta<typeof TextEntry>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
    args: {
        label: 'Student Name',
        value: 'John Doe',
    },
};

export const WithEmail: Story = {
    args: {
        label: 'Email Address',
        value: 'john.doe@example.com',
    },
};

export const WithLongValue: Story = {
    args: {
        label: 'Description',
        value: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    },
};

export const WithCustomStyles: Story = {
    args: {
        label: 'Total Score',
        value: '85/100',
        className: 'bg-indigo-50 p-4 rounded-lg',
        labelClass: 'text-indigo-900',
        valueClass: 'text-indigo-700 text-lg font-bold',
    },
};

export const WithReactNodeLabel: Story = {
    args: {
        label: (
            <div className="flex items-center space-x-2">
                <span className="text-sm font-bold text-gray-900">Status</span>
                <Badge label="Active" type="success" />
            </div>
        ),
        value: 'Currently enrolled',
    },
};

export const WithReactNodeValue: Story = {
    args: {
        label: 'Assessment Status',
        value: (
            <div className="flex items-center space-x-2">
                <Badge label="Completed" type="success" />
                <span className="text-sm text-gray-600">Submitted on 2024-01-15</span>
            </div>
        ),
    },
};

export const MultipleEntries = {
    render: () => (
        <div className="space-y-4 w-96">
            <TextEntry label="Student ID" value="2024001" />
            <TextEntry label="Full Name" value="John Doe" />
            <TextEntry label="Email" value="john.doe@example.com" />
            <TextEntry label="Class" value="Computer Science - Year 3" />
            <TextEntry label="Status" value={<Badge label="Active" type="success" />} />
        </div>
    ),
} as unknown as Story;

export const StudentProfile = {
    render: () => (
        <div className="bg-white p-6 rounded-lg border border-gray-200 w-96 space-y-4">
            <h3 className="text-lg font-bold text-gray-900 mb-4">Student Profile</h3>
            <TextEntry label="Name" value="Alice Johnson" />
            <TextEntry label="Student ID" value="ST2024001" />
            <TextEntry label="Email" value="alice.johnson@university.edu" />
            <TextEntry label="Department" value="Mathematics" />
            <TextEntry label="Year" value="2nd Year" />
            <TextEntry label="Status" value={<Badge label="Active" type="success" />} />
            <TextEntry label="GPA" value="3.85/4.00" valueClass="text-green-600 font-bold" />
        </div>
    ),
} as unknown as Story;

export const AssessmentDetails = {
    render: () => (
        <div className="bg-white p-6 rounded-lg border border-gray-200 w-96 space-y-4">
            <h3 className="text-lg font-bold text-gray-900 mb-4">Assessment Information</h3>
            <TextEntry label="Assessment Title" value="Final Assessment - Mathematics" />
            <TextEntry label="Duration" value="2 hours" />
            <TextEntry label="Total Questions" value="50" />
            <TextEntry label="Passing Score" value="70%" />
            <TextEntry label="Status" value={<Badge label="In Progress" type="warning" />} />
            <TextEntry
                label="Time Remaining"
                value="45 minutes"
                valueClass="text-orange-600 font-bold"
            />
        </div>
    ),
};
