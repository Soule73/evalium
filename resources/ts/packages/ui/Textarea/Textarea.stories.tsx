import type { Meta, StoryObj } from '@storybook/react';
import { useState } from 'react';
import Textarea from './Textarea';

const meta = {
    title: 'UI/Textarea',
    component: Textarea,
    parameters: {
        layout: 'centered',
    },
} satisfies Meta<typeof Textarea>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default = {
    render: () => {
        const [value, setValue] = useState('');
        return (
            <div className="w-96">
                <Textarea
                    label="Description"
                    placeholder="Enter your description here..."
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                />
            </div>
        );
    },
} as Story;

export const WithValue = {
    render: () => {
        const [value, setValue] = useState(
            'This is a sample text that demonstrates the textarea component with initial content.',
        );
        return (
            <div className="w-96">
                <Textarea
                    label="Comments"
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                />
            </div>
        );
    },
} as Story;

export const WithError = {
    render: () => {
        const [value, setValue] = useState('');
        return (
            <div className="w-96">
                <Textarea
                    label="Feedback"
                    placeholder="Enter your feedback..."
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    error="This field is required"
                />
            </div>
        );
    },
} as Story;

export const WithHelperText = {
    render: () => {
        const [value, setValue] = useState('');
        return (
            <div className="w-96">
                <Textarea
                    label="Essay Answer"
                    placeholder="Write your essay here..."
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    helperText="Minimum 200 words required"
                />
            </div>
        );
    },
} as Story;

export const Disabled = {
    render: () => {
        return (
            <div className="w-96">
                <Textarea
                    label="Read Only Content"
                    value="This textarea is disabled and cannot be edited."
                    disabled
                />
            </div>
        );
    },
} as Story;

export const CustomRows = {
    render: () => {
        const [value, setValue] = useState('');
        return (
            <div className="w-96">
                <Textarea
                    label="Long Form Answer"
                    placeholder="Write your detailed answer..."
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    rows={8}
                />
            </div>
        );
    },
} as Story;

export const WithCharacterCount = {
    render: () => {
        const [value, setValue] = useState('');
        const maxLength = 500;

        return (
            <div className="w-96">
                <Textarea
                    label="Limited Input"
                    placeholder="Maximum 500 characters..."
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    maxLength={maxLength}
                    helperText={`${value.length}/${maxLength} characters`}
                />
            </div>
        );
    },
} as Story;

export const AssessmentAnswer = {
    render: () => {
        const [value, setValue] = useState('');
        const wordCount = value.trim().split(/\s+/).filter(Boolean).length;
        const minWords = 50;
        const hasError = value && wordCount < minWords;

        return (
            <div className="w-96">
                <Textarea
                    label="Question 1: Explain the concept of polymorphism in OOP"
                    placeholder="Write your answer here..."
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    rows={6}
                    error={hasError ? `Minimum ${minWords} words required` : undefined}
                    helperText={!hasError ? `Word count: ${wordCount}/${minWords}` : undefined}
                />
            </div>
        );
    },
} as Story;

export const FeedbackForm = {
    render: () => {
        const [feedback, setFeedback] = useState('');

        return (
            <div className="w-96 space-y-4">
                <h3 className="text-lg font-bold text-gray-900">Course Feedback</h3>
                <Textarea
                    label="What did you like about this course?"
                    placeholder="Share your positive experiences..."
                    value={feedback}
                    onChange={(e) => setFeedback(e.target.value)}
                    rows={4}
                />
                <Textarea
                    label="What could be improved?"
                    placeholder="Share your suggestions..."
                    rows={4}
                />
                <Textarea
                    label="Additional Comments (Optional)"
                    placeholder="Any other feedback..."
                    rows={3}
                    helperText="This field is optional"
                />
            </div>
        );
    },
} as Story;

export const Required = {
    render: () => {
        const [value, setValue] = useState('');
        const [touched, setTouched] = useState(false);
        const hasError = touched && !value.trim();

        return (
            <div className="w-96">
                <Textarea
                    label="Required Field *"
                    placeholder="This field is required..."
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    onBlur={() => setTouched(true)}
                    error={hasError ? 'This field is required' : undefined}
                    required
                />
            </div>
        );
    },
};
