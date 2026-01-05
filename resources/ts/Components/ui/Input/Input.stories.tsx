import type { Meta, StoryObj } from '@storybook/react';
import Input from './Input';

const meta: Meta<typeof Input> = {
    title: 'UI/Forms/Input',
    component: Input,
    parameters: {
        layout: 'centered',
    },
    tags: ['autodocs'],
    argTypes: {
        type: {
            control: 'select',
            options: ['text', 'email', 'password', 'number', 'tel', 'url', 'search'],
        },
        disabled: { control: 'boolean' },
        required: { control: 'boolean' },
    },
};

export default meta;
type Story = StoryObj<typeof Input>;

export const Default: Story = {
    args: {
        placeholder: 'Enter text...',
    },
};

export const WithLabel: Story = {
    args: {
        label: 'Email Address',
        type: 'email',
        placeholder: 'you@example.com',
    },
};

export const WithError: Story = {
    args: {
        label: 'Username',
        placeholder: 'Enter username',
        error: 'Username is required',
        defaultValue: '',
    },
};

export const WithHelperText: Story = {
    args: {
        label: 'Password',
        type: 'password',
        placeholder: 'Enter your password',
        helperText: 'Must be at least 8 characters',
    },
};

export const Disabled: Story = {
    args: {
        label: 'Disabled Input',
        placeholder: 'Cannot edit',
        disabled: true,
        defaultValue: 'Read only value',
    },
};

export const Required: Story = {
    args: {
        label: 'Required Field',
        placeholder: 'This field is required',
        required: true,
    },
};

export const DifferentTypes: Story = {
    render: () => (
        <div className="space-y-4 w-80">
            <Input type="text" label="Text" placeholder="Text input" />
            <Input type="email" label="Email" placeholder="email@example.com" />
            <Input type="password" label="Password" placeholder="********" />
            <Input type="number" label="Number" placeholder="123" />
            <Input type="tel" label="Phone" placeholder="+1 (555) 000-0000" />
            <Input type="url" label="URL" placeholder="https://example.com" />
            <Input type="search" label="Search" placeholder="Search..." />
        </div>
    ),
} as any;
