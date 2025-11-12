import { useForm } from "@inertiajs/react";
import { route } from "ziggy-js";

const useLogin = () => {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('login.attempt'));
    };

    return {
        data,
        setData,
        post,
        processing,
        errors,
        handleSubmit,
    };
};

export default useLogin;
