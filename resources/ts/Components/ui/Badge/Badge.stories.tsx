import type { Meta, StoryObj } from "@storybook/react";
import Badge from "./Badge";

const meta = {
    title: "UI/Badge",
    component: Badge,
    parameters: {
        layout: "centered",
    },
    argTypes: {
        type: {
            control: "select",
            options: ["success", "error", "warning", "info", "gray"],
        },
    },
} satisfies Meta<typeof Badge>;

export default meta;
type Story = StoryObj<typeof meta>;

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type CustomStory = any;

export const Success: Story = {
    args: {
        label: "Success",
        type: "success",
    },
};

export const Error: Story = {
    args: {
        label: "Error",
        type: "error",
    },
};

export const Warning: Story = {
    args: {
        label: "Warning",
        type: "warning",
    },
};

export const Info: Story = {
    args: {
        label: "Info",
        type: "info",
    },
};

export const Gray: Story = {
    args: {
        label: "Gray",
        type: "gray",
    },
};

export const Active: Story = {
    args: {
        label: "Active",
        type: "success",
    },
};

export const Inactive: Story = {
    args: {
        label: "Inactive",
        type: "gray",
    },
};

export const Pending: Story = {
    args: {
        label: "Pending",
        type: "warning",
    },
};

export const Completed: Story = {
    args: {
        label: "Completed",
        type: "info",
    },
};

export const Failed: Story = {
    args: {
        label: "Failed",
        type: "error",
    },
};

export const AllTypes = {
    render: () => (
        <div className="flex flex-wrap gap-2">
            <Badge label="Success" type="success" />
            <Badge label="Error" type="error" />
            <Badge label="Warning" type="warning" />
            <Badge label="Info" type="info" />
            <Badge label="Gray" type="gray" />
        </div>
    ),
} as CustomStory;
