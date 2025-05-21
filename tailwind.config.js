import forms from "@tailwindcss/forms";
import defaultTheme from "tailwindcss/defaultTheme";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Space Grotesk", ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                xxs: "0.625rem",
            },
        },
    },

    plugins: [forms],
};
