import type { Meta, StoryObj } from '@storybook/react';
import Checkbox from './Checkbox';

const meta: Meta<typeof Checkbox> = {
    title: 'UI/Forms/Checkbox',
    component: Checkbox,
    parameters: {
        layout: 'centered',
    },
    tags: ['autodocs'],
    argTypes: {
        disabled: { control: 'boolean' },
        checked: { control: 'boolean' },
        type: {
            control: 'select',
            options: ['checkbox', 'radio'],
        },
    },
};

export default meta;
type Story = StoryObj<typeof Checkbox>;

export const Default: Story = {
    args: {
        label: 'Accept terms and conditions',
    },
};

export const Checked: Story = {
    args: {
        label: 'I agree',
        defaultChecked: true,
    },
};

export const WithoutLabel: Story = {
    args: {},
};

export const Disabled: Story = {
    args: {
        label: 'Disabled option',
        disabled: true,
    },
};

export const DisabledChecked: Story = {
    args: {
        label: 'Read only selected',
        disabled: true,
        defaultChecked: true,
    },
};

export const WithError: Story = {
    args: {
        label: 'Accept required terms',
        error: 'You must accept the terms to continue',
    },
};

export const RadioButton: Story = {
    args: {
        type: 'radio',
        label: 'Radio option',
        name: 'radio-group',
    },
};

export const CheckboxGroup: Story = {
    render: () => (
        <div className="space-y-2">
            <Checkbox label="Option 1" name="options" />
            <Checkbox label="Option 2" name="options" defaultChecked />
            <Checkbox label="Option 3" name="options" />
            <Checkbox label="Option 4 (Disabled)" name="options" disabled />
        </div>
    ),
} as any;

export const RadioGroup: Story = {
    render: () => (
        <div className="space-y-2">
            <Checkbox type="radio" label="Small" name="size" defaultChecked />
            <Checkbox type="radio" label="Medium" name="size" />
            <Checkbox type="radio" label="Large" name="size" />
            <Checkbox type="radio" label="Extra Large (Disabled)" name="size" disabled />
        </div>
    ),
} as any;
