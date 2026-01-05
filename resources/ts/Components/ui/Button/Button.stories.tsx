import type { Meta, StoryObj } from "@storybook/react";
import Button from "./Button";

const meta = {
    title: "UI/Button",
    component: Button,
    parameters: {
        layout: "centered",
    },
    argTypes: {
        color: {
            control: "select",
            options: ["primary", "secondary", "danger", "success", "warning"],
        },
        variant: {
            control: "select",
            options: ["solid", "outline", "ghost"],
        },
        size: {
            control: "select",
            options: ["sm", "md", "lg"],
        },
        loading: {
            control: "boolean",
        },
        disabled: {
            control: "boolean",
        },
    },
} satisfies Meta<typeof Button>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Primary: Story = {
    args: {
        children: "Button",
        color: "primary",
        variant: "solid",
        size: "md",
    },
};

export const Secondary: Story = {
    args: {
        children: "Button",
        color: "secondary",
    },
};

export const Danger: Story = {
    args: {
        children: "Delete",
        color: "danger",
    },
};

export const Success: Story = {
    args: {
        children: "Save",
        color: "success",
    },
};

export const Outline: Story = {
    args: {
        children: "Button",
        variant: "outline",
    },
};

export const Ghost: Story = {
    args: {
        children: "Button",
        variant: "ghost",
    },
};

export const Small: Story = {
    args: {
        children: "Small Button",
        size: "sm",
    },
};

export const Large: Story = {
    args: {
        children: "Large Button",
        size: "lg",
    },
};

export const Loading: Story = {
    args: {
        children: "Loading...",
        loading: true,
    },
};

export const Disabled: Story = {
    args: {
        children: "Disabled",
        disabled: true,
    },
};
