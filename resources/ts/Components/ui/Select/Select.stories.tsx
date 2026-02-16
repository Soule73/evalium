import type { Meta, StoryObj } from '@storybook/react';
import { useState } from 'react';
import Select from './Select';

const meta = {
    title: 'UI/Select',
    component: Select,
    parameters: {
        layout: 'centered',
    },
    argTypes: {
        searchable: {
            control: 'boolean',
        },
        disabled: {
            control: 'boolean',
        },
    },
} satisfies Meta<typeof Select>;

export default meta;
type Story = StoryObj<typeof meta>;

const countries = [
    { value: 'fr', label: 'France' },
    { value: 'us', label: 'United States' },
    { value: 'uk', label: 'United Kingdom' },
    { value: 'de', label: 'Germany' },
    { value: 'es', label: 'Spain' },
    { value: 'it', label: 'Italy' },
    { value: 'ca', label: 'Canada' },
    { value: 'au', label: 'Australia' },
    { value: 'jp', label: 'Japan' },
    { value: 'cn', label: 'China' },
];

const roles = [
    { value: 'admin', label: 'Administrator' },
    { value: 'teacher', label: 'Teacher' },
    { value: 'student', label: 'Student' },
];

export const Default = {
    render: () => {
        const [value, setValue] = useState<string | number>('');
        return (
            <div className="w-80">
                <Select
                    label="Select a country"
                    options={countries}
                    placeholder="Choose a country"
                    value={value}
                    onChange={setValue}
                />
            </div>
        );
    },
} as unknown as Story;

export const WithValue = {
    render: () => {
        const [value, setValue] = useState<string | number>('fr');
        return (
            <div className="w-80">
                <Select
                    label="Select a country"
                    options={countries}
                    placeholder="Choose a country"
                    value={value}
                    onChange={setValue}
                />
            </div>
        );
    },
} as unknown as Story;

export const WithError = {
    render: () => {
        const [value, setValue] = useState<string | number>('');
        return (
            <div className="w-80">
                <Select
                    label="Select a country"
                    options={countries}
                    placeholder="Choose a country"
                    value={value}
                    onChange={setValue}
                    error="Please select a country"
                />
            </div>
        );
    },
} as unknown as Story;

export const WithHelperText = {
    render: () => {
        const [value, setValue] = useState<string | number>('');
        return (
            <div className="w-80">
                <Select
                    label="Select a country"
                    options={countries}
                    placeholder="Choose a country"
                    value={value}
                    onChange={setValue}
                    helperText="This will be used for billing purposes"
                />
            </div>
        );
    },
} as unknown as Story;

export const Disabled = {
    render: () => {
        const [value, setValue] = useState<string | number>('fr');
        return (
            <div className="w-80">
                <Select
                    label="Select a country"
                    options={countries}
                    placeholder="Choose a country"
                    value={value}
                    onChange={setValue}
                    disabled
                />
            </div>
        );
    },
} as unknown as Story;

export const NotSearchable = {
    render: () => {
        const [value, setValue] = useState<string | number>('');
        return (
            <div className="w-80">
                <Select
                    label="Select a role"
                    options={roles}
                    placeholder="Choose a role"
                    value={value}
                    onChange={setValue}
                    searchable={false}
                />
            </div>
        );
    },
} as unknown as Story;

export const WithDisabledOptions = {
    render: () => {
        const [value, setValue] = useState<string | number>('');
        const optionsWithDisabled = [
            { value: 'admin', label: 'Administrator', disabled: true },
            { value: 'teacher', label: 'Teacher' },
            { value: 'student', label: 'Student' },
        ];
        return (
            <div className="w-80">
                <Select
                    label="Select a role"
                    options={optionsWithDisabled}
                    placeholder="Choose a role"
                    value={value}
                    onChange={setValue}
                />
            </div>
        );
    },
} as unknown as Story;

export const EmptyOptions = {
    render: () => {
        const [value, setValue] = useState<string | number>('');
        return (
            <div className="w-80">
                <Select
                    label="Select an option"
                    options={[]}
                    placeholder="No options available"
                    value={value}
                    onChange={setValue}
                    noOptionFound="No options found"
                />
            </div>
        );
    },
} as unknown as Story;
