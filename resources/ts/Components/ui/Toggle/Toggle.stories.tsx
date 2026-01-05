import type { Meta, StoryObj } from '@storybook/react';
import { useState } from 'react';
import Toggle from './Toggle';

const meta: Meta<typeof Toggle> = {
    title: 'UI/Forms/Toggle',
    component: Toggle,
    parameters: {
        layout: 'centered',
    },
    tags: ['autodocs'],
    argTypes: {
        size: {
            control: 'select',
            options: ['sm', 'md', 'lg'],
        },
        color: {
            control: 'select',
            options: ['blue', 'green', 'purple', 'red', 'gray'],
        },
        disabled: { control: 'boolean' },
        showLabel: { control: 'boolean' },
    },
};

export default meta;
type Story = StoryObj<typeof Toggle>;

export const Default: Story = {
    args: {
        checked: false,
    },
};

export const Checked: Story = {
    args: {
        checked: true,
    },
};

export const WithLabel: Story = {
    args: {
        label: 'Enable notifications',
        checked: false,
    },
};

export const WithStatusLabel: Story = {
    args: {
        label: 'Dark mode',
        showLabel: true,
        activeLabel: 'On',
        inactiveLabel: 'Off',
        checked: false,
    },
};

export const Disabled: Story = {
    args: {
        label: 'Disabled toggle',
        disabled: true,
        checked: false,
    },
};

export const DisabledChecked: Story = {
    args: {
        label: 'Disabled checked',
        disabled: true,
        checked: true,
    },
};

export const SmallSize: Story = {
    args: {
        size: 'sm',
        label: 'Small toggle',
    },
};

export const LargeSize: Story = {
    args: {
        size: 'lg',
        label: 'Large toggle',
    },
};

export const Colors: Story = {
    render: () => (
        <div className="space-y-3">
            <Toggle color="blue" label="Blue" checked showLabel activeLabel="Active" inactiveLabel="Inactive" />
            <Toggle color="green" label="Green" checked showLabel activeLabel="Active" inactiveLabel="Inactive" />
            <Toggle color="purple" label="Purple" checked showLabel activeLabel="Active" inactiveLabel="Inactive" />
            <Toggle color="red" label="Red" checked showLabel activeLabel="Active" inactiveLabel="Inactive" />
            <Toggle color="gray" label="Gray" checked showLabel activeLabel="Active" inactiveLabel="Inactive" />
        </div>
    ),
} as any;

export const Sizes: Story = {
    render: () => (
        <div className="space-y-3">
            <Toggle size="sm" label="Small" checked />
            <Toggle size="md" label="Medium" checked />
            <Toggle size="lg" label="Large" checked />
        </div>
    ),
} as any;

export const Interactive: Story = {
    render: () => {
        const [checked, setChecked] = useState(false);
        return (
            <div className="space-y-4">
                <Toggle
                    checked={checked}
                    onChange={setChecked}
                    label="Toggle me"
                    showLabel
                    activeLabel="Enabled"
                    inactiveLabel="Disabled"
                />
                <p className="text-sm text-gray-600">
                    Status: {checked ? 'Enabled' : 'Disabled'}
                </p>
            </div>
        );
    },
} as any;
