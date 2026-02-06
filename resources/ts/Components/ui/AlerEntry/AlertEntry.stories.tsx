import type { Meta, StoryObj } from "@storybook/react";
import AlertEntry from "./AlertEntry";

const meta = {
    title: "UI/AlertEntry",
    component: AlertEntry,
    parameters: {
        layout: "padded",
    },

    argTypes: {
        type: {
            control: "select",
            options: ["success", "error", "warning", "info"],
        },
    },
} satisfies Meta<typeof AlertEntry>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Success: Story = {
    args: {
        title: "Success",
        type: "success",
        children: <p>Your changes have been saved successfully.</p>,
    },
};

export const Error: Story = {
    args: {
        title: "Error",
        type: "error",
        children: <p>Something went wrong. Please try again.</p>,
    },
};

export const Warning: Story = {
    args: {
        title: "Warning",
        type: "warning",
        children: (
            <p>This action cannot be undone. Please proceed with caution.</p>
        ),
    },
};

export const Info: Story = {
    args: {
        title: "Information",
        type: "info",
        children: <p>You have 3 pending assessment submissions to review.</p>,
    },
};

export const WithoutChildren: Story = {
    args: {
        title: "Simple Alert",
        type: "info",
    },
};

export const WithLongContent: Story = {
    args: {
        title: "Detailed Information",
        type: "info",
        children: (
            <div>
                <p className="mb-2">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed
                    do eiusmod tempor incididunt ut labore et dolore magna
                    aliqua.
                </p>
                <ul className="list-disc list-inside space-y-1">
                    <li>Point number one</li>
                    <li>Point number two</li>
                    <li>Point number three</li>
                </ul>
            </div>
        ),
    },
};

export const AssessmentSubmitted: Story = {
    args: {
        title: "Assessment Submitted Successfully",
        type: "success",
        children: (
            <p>
                Your assessment has been submitted. You will receive your results
                within 48 hours.
            </p>
        ),
    },
};

export const ValidationError: Story = {
    args: {
        title: "Validation Error",
        type: "error",
        children: (
            <div>
                <p className="mb-2">Please fix the following errors:</p>
                <ul className="list-disc list-inside space-y-1">
                    <li>Email address is required</li>
                    <li>Password must be at least 8 characters</li>
                    <li>Please accept the terms and conditions</li>
                </ul>
            </div>
        ),
    },
};

export const TimeWarning: Story = {
    args: {
        title: "Time Running Out",
        type: "warning",
        children: (
            <p>
                You have only <strong>5 minutes</strong> remaining to complete
                your assessment. Please submit your answers before time runs out.
            </p>
        ),
    },
};

export const SystemMaintenance: Story = {
    args: {
        title: "Scheduled Maintenance",
        type: "info",
        children: (
            <div>
                <p className="mb-2">The system will be under maintenance:</p>
                <ul className="list-disc list-inside space-y-1">
                    <li>Date: Saturday, January 20, 2024</li>
                    <li>Time: 2:00 AM - 6:00 AM (EST)</li>
                    <li>Expected downtime: 4 hours</li>
                </ul>
                <p className="mt-2">
                    Please plan your assessment schedule accordingly.
                </p>
            </div>
        ),
    },
};

export const AllTypes = {
    render: () => (
        <div className="space-y-4 max-w-2xl">
            <AlertEntry title="Success Alert" type="success">
                <p>This is a success message.</p>
            </AlertEntry>
            <AlertEntry title="Error Alert" type="error">
                <p>This is an error message.</p>
            </AlertEntry>
            <AlertEntry title="Warning Alert" type="warning">
                <p>This is a warning message.</p>
            </AlertEntry>
            <AlertEntry title="Info Alert" type="info">
                <p>This is an info message.</p>
            </AlertEntry>
        </div>
    ),
} as unknown as Story;
