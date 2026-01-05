import type { Meta, StoryObj } from "@storybook/react";
import { useState } from "react";
import Modal from "./Modal";
import Button from "../Button/Button";

const meta = {
    title: "UI/Modal",
    component: Modal,
    parameters: {
        layout: "centered",
    },
    argTypes: {
        size: {
            control: "select",
            options: ["sm", "md", "lg", "xl", "2xl", "full"],
        },
        isCloseableInside: {
            control: "boolean",
        },
    },
} satisfies Meta<typeof Modal>;

export default meta;
type Story = StoryObj<typeof meta>;

const ModalWrapper = ({
    size,
    isCloseableInside,
}: {
    size?: "sm" | "md" | "lg" | "xl" | "2xl" | "full";
    isCloseableInside?: boolean;
}) => {
    const [isOpen, setIsOpen] = useState(false);

    return (
        <div>
            <Button onClick={() => setIsOpen(true)}>Open Modal</Button>
            <Modal
                isOpen={isOpen}
                onClose={() => setIsOpen(false)}
                size={size}
                isCloseableInside={isCloseableInside}
            >
                <h2 className="text-xl font-bold mb-4">Modal Title</h2>
                <p className="text-gray-600 mb-4">
                    This is a modal dialog. It can contain any content you want
                    to display.
                </p>
                <div className="flex justify-end space-x-2">
                    <Button
                        color="secondary"
                        variant="outline"
                        onClick={() => setIsOpen(false)}
                    >
                        Cancel
                    </Button>
                    <Button color="primary" onClick={() => setIsOpen(false)}>
                        Confirm
                    </Button>
                </div>
            </Modal>
        </div>
    );
};

export const Small = {
    render: () => <ModalWrapper size="sm" />,
} as unknown as Story;

export const Medium = {
    render: () => <ModalWrapper size="md" />,
} as unknown as Story;

export const Large = {
    render: () => <ModalWrapper size="lg" />,
} as unknown as Story;

export const ExtraLarge = {
    render: () => <ModalWrapper size="xl" />,
} as unknown as Story;

export const ExtraExtraLarge = {
    render: () => <ModalWrapper size="2xl" />,
} as unknown as Story;

export const FullScreen = {
    render: () => <ModalWrapper size="full" />,
} as unknown as Story;

export const NotCloseable = {
    render: () => <ModalWrapper isCloseableInside={false} />,
} as unknown as Story;

export const WithLongContent = {
    render: () => {
        const [isOpen, setIsOpen] = useState(false);

        return (
            <div>
                <Button onClick={() => setIsOpen(true)}>
                    Open Modal with Long Content
                </Button>
                <Modal
                    isOpen={isOpen}
                    onClose={() => setIsOpen(false)}
                    size="lg"
                >
                    <h2 className="text-xl font-bold mb-4">
                        Terms and Conditions
                    </h2>
                    <div className="text-gray-600 mb-4 max-h-96 overflow-y-auto">
                        {Array.from({ length: 20 }, (_, i) => (
                            <p key={i} className="mb-2">
                                Lorem ipsum dolor sit amet, consectetur
                                adipiscing elit. Sed do eiusmod tempor
                                incididunt ut labore et dolore magna aliqua.
                            </p>
                        ))}
                    </div>
                    <div className="flex justify-end">
                        <Button
                            color="primary"
                            onClick={() => setIsOpen(false)}
                        >
                            I Agree
                        </Button>
                    </div>
                </Modal>
            </div>
        );
    },
} as unknown as Story;
