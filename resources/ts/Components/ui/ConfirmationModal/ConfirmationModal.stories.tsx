import type { Meta, StoryObj } from "@storybook/react";
import { useState } from "react";
import ConfirmationModal from "./ConfirmationModal";
import Button from "../Button/Button";
import { TrashIcon, InformationCircleIcon } from "@heroicons/react/24/outline";

const meta = {
    title: "UI/ConfirmationModal",
    component: ConfirmationModal,
    parameters: {
        layout: "centered",
    },
    argTypes: {
        type: {
            control: "select",
            options: ["danger", "warning", "info"],
        },
        loading: {
            control: "boolean",
        },
        isCloseableInside: {
            control: "boolean",
        },
    },
} satisfies Meta<typeof ConfirmationModal>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Danger = {
    args:{
        type:"warning"
    },
    render:() => {
        const [isOpen, setIsOpen] = useState(false);

        return (
            <div>
                <Button color="danger" onClick={() => setIsOpen(true)}>
                    Delete Item
                </Button>
                <ConfirmationModal
                    isOpen={isOpen}
                    onClose={() => setIsOpen(false)}
                    onConfirm={() => {
                        alert("Item deleted!");
                        setIsOpen(false);
                    }}
                    title="Delete Item"
                    message="Are you sure you want to delete this item? This action cannot be undone."
                    confirmText="Delete"
                    cancelText="Cancel"
                    type="danger"
                    icon={TrashIcon}
                />
            </div>
        );
    }
} as unknown as Story;

export const Warning = {
    render:() => {
        const [isOpen, setIsOpen] = useState(false);

        return (
            <div>
                <Button color="warning" onClick={() => setIsOpen(true)}>
                    Submit Exam
                </Button>
                <ConfirmationModal
                    isOpen={isOpen}
                    onClose={() => setIsOpen(false)}
                    onConfirm={() => {
                        alert("Exam submitted!");
                        setIsOpen(false);
                    }}
                    title="Submit Exam"
                    message="Are you sure you want to submit your exam? You won't be able to change your answers after submission."
                    confirmText="Submit"
                    cancelText="Cancel"
                    type="warning"
                />
            </div>
        );
    },
} as unknown as Story;

export const Info = {
    render:() => {
        const [isOpen, setIsOpen] = useState(false);

        return (
            <div>
                <Button color="primary" onClick={() => setIsOpen(true)}>
                    Save Changes
                </Button>
                <ConfirmationModal
                    isOpen={isOpen}
                    onClose={() => setIsOpen(false)}
                    onConfirm={() => {
                        alert("Changes saved!");
                        setIsOpen(false);
                    }}
                    title="Save Changes"
                    message="Do you want to save the changes you made?"
                    confirmText="Save"
                    cancelText="Cancel"
                    type="info"
                    icon={InformationCircleIcon}
                />
            </div>
        );
    },
} as unknown as Story;

export const WithLoading = {
    render:() => {
        const [isOpen, setIsOpen] = useState(false);
        const [loading, setLoading] = useState(false);

        const handleConfirm = () => {
            setLoading(true);
            setTimeout(() => {
                setLoading(false);
                setIsOpen(false);
                alert("Action completed!");
            }, 2000);
        };

        return (
            <div>
                <Button color="danger" onClick={() => setIsOpen(true)}>
                    Delete with Loading
                </Button>
                <ConfirmationModal
                    isOpen={isOpen}
                    onClose={() => setIsOpen(false)}
                    onConfirm={handleConfirm}
                    title="Delete Item"
                    message="This will delete the item permanently."
                    confirmText="Delete"
                    cancelText="Cancel"
                    type="danger"
                    loading={loading}
                />
            </div>
        );
    },
} as unknown as Story;

export const NotCloseable = {
    render:() => {
        const [isOpen, setIsOpen] = useState(false);

        return (
            <div>
                <Button color="warning" onClick={() => setIsOpen(true)}>
                    Open Non-Closeable Modal
                </Button>
                <ConfirmationModal
                    isOpen={isOpen}
                    onClose={() => setIsOpen(false)}
                    onConfirm={() => {
                        alert("Confirmed!");
                        setIsOpen(false);
                    }}
                    title="Important Action"
                    message="You must either confirm or cancel this action."
                    confirmText="Confirm"
                    cancelText="Cancel"
                    type="warning"
                    isCloseableInside={false}
                />
            </div>
        );
    },
} as unknown as Story;

export const WithCustomContent = {
    render:() => {
        const [isOpen, setIsOpen] = useState(false);

        return (
            <div>
                <Button color="danger" onClick={() => setIsOpen(true)}>
                    Delete Account
                </Button>
                <ConfirmationModal
                    isOpen={isOpen}
                    onClose={() => setIsOpen(false)}
                    onConfirm={() => {
                        alert("Account deleted!");
                        setIsOpen(false);
                    }}
                    title="Delete Account"
                    message="This will permanently delete your account and all associated data."
                    confirmText="Delete Account"
                    cancelText="Cancel"
                    type="danger"
                    size="lg"
                >
                    <div className="w-full mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p className="text-sm text-red-800 font-medium">
                            Warning:
                        </p>
                        <ul className="text-sm text-red-700 list-disc list-inside mt-2 space-y-1">
                            <li>All your exams will be deleted</li>
                            <li>All your grades will be lost</li>
                            <li>This action cannot be undone</li>
                        </ul>
                    </div>
                </ConfirmationModal>
            </div>
        );
    },
} as unknown as Story;

export const LargeModal = {
    render:() => {
        const [isOpen, setIsOpen] = useState(false);

        return (
            <div>
                <Button color="primary" onClick={() => setIsOpen(true)}>
                    Open Large Modal
                </Button>
                <ConfirmationModal
                    isOpen={isOpen}
                    onClose={() => setIsOpen(false)}
                    onConfirm={() => {
                        alert("Confirmed!");
                        setIsOpen(false);
                    }}
                    title="Large Confirmation"
                    message="This is a larger confirmation modal with more space for content."
                    confirmText="Confirm"
                    cancelText="Cancel"
                    type="info"
                    size="xl"
                />
            </div>
        );
    },
};
