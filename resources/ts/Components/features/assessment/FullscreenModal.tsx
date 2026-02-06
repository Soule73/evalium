import { ArrowsPointingOutIcon } from "@heroicons/react/24/outline";
import { trans } from '@/utils';
import { Modal, Button } from "@/Components/ui";

interface FullscreenModalProps {
    isOpen: boolean;
    onEnterFullscreen: () => void;
}

function FullscreenModal({ isOpen, onEnterFullscreen }: FullscreenModalProps) {
    return (
        <Modal isOpen={isOpen} onClose={() => { }}>
            <div className="p-6">
                <div className="flex items-center justify-center mb-4">
                    <div className="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full">
                        <ArrowsPointingOutIcon
                            className="w-6 h-6 text-blue-600" />
                    </div>
                </div>

                <div className="text-center mb-6">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">
                        {trans('components.fullscreen_modal.title')}
                    </h3>
                    <p className="text-gray-600">
                        {trans('components.fullscreen_modal.description_line1')}
                        {' '}
                        {trans('components.fullscreen_modal.description_line2')}
                    </p>
                </div>

                <div className="flex justify-center">
                    <Button
                        size="md"
                        color="primary"
                        variant='outline'
                        onClick={onEnterFullscreen}
                        className="flex items-center"
                    >
                        <ArrowsPointingOutIcon className="w-4 h-4 mr-2" />
                        {trans('components.fullscreen_modal.button')}
                    </Button>
                </div>
            </div>
        </Modal>
    );
}

export { FullscreenModal };