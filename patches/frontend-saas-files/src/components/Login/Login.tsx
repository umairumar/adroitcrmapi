import { Button, Checkbox, Label, TextInput } from "flowbite-react";
import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import Swal from "sweetalert2";
import Logo from "../../assets/images/adroit-logo.png";
import Cookies from "js-cookie";
import { ForgerPassAPI } from "../../api/ForgetPassword";
import { ResetPass } from "../../api/ResetPassword";
import { HiEye, HiEyeOff } from "react-icons/hi";
import api, { rootApi, usesApiProxy } from "../../api/api";
import { routePrefixForUtype, storageRoleFromUtype } from "../../utils/role";

const Login = () => {
    const [errors, setErrors] = useState<Record<string, string>>({});

    const [email, setEmail] = useState("");
    const [loading, setLoading] = useState(false);
    const [mode, setMode] = useState<"login" | "forgot" | "reset">("login");
    const [confirmPassword, setConfirmPassword] = useState("");
    const [password, setPassword] = useState("");
    const [showPassword, setShowPassword] = useState(false);
    const [token, setToken] = useState("");
    const [newPassword, setNewPassword] = useState("");
    const [rememberMe, setRememberMe] = useState(false);

    const navigate = useNavigate();

    // Load saved credentials
    useEffect(() => {
        const savedEmail = Cookies.get("remember_email");
        const savedPassword = Cookies.get("remember_password");
        if (savedEmail && savedPassword) {
            setEmail(savedEmail);
            setPassword(savedPassword);
            setRememberMe(true);
        }
    }, []);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!validate()) return;

        setLoading(true);

        try {
            // ================= LOGIN =================
            if (mode === "login") {
                if (!password) {
                    Swal.fire("Error", "Please enter your password", "error");
                    return;
                }

                const payload = { email, password };
                if (usesApiProxy) {
                    await rootApi.get("/sanctum/csrf-cookie");
                }
                const response = await api.post("/login", payload);
                const { token, user } = response.data;

                localStorage.setItem("token", token);
                localStorage.setItem("role", storageRoleFromUtype(user.utype));
                localStorage.setItem("user", JSON.stringify(user));
                if (response.data.tenant) {
                    localStorage.setItem("tenant", JSON.stringify(response.data.tenant));
                }

                if (rememberMe) {
                    Cookies.set("remember_email", email, { expires: 7 });
                    Cookies.set("remember_password", password, { expires: 7 });
                } else {
                    Cookies.remove("remember_email");
                    Cookies.remove("remember_password");
                }

                const prefix = routePrefixForUtype(user.utype);
                if (prefix) {
                    navigate(`/${prefix}`);
                } else {
                    Swal.fire("Access Denied", "Your role is not authorized", "error");
                }
            }

            // ================= FORGOT =================
            else if (mode === "forgot") {
                const response = await ForgerPassAPI.sendOTP(email);

                if (response.data.status) {
                    Swal.fire("Success", response.data.message, "success");
                    setMode("reset");
                } else {
                    Swal.fire("Error", response.data.message || "Failed to send OTP", "error");
                }
            }

            // ================= RESET =================
            else if (mode === "reset") {
                if (!token || !newPassword || !confirmPassword) {
                    Swal.fire("Error", "All fields are required", "error");
                    return;
                }

                if (newPassword !== confirmPassword) {
                    Swal.fire("Error", "Passwords do not match", "error");
                    return;
                }

                const response = await ResetPass.resetPassword({
                    email,
                    token,
                    password: newPassword,
                    password_confirmation: confirmPassword,
                });

                if (response.data.status) {
                    Swal.fire("Success", response.data.message, "success");
                    setMode("login");
                    setToken("");
                    setNewPassword("");
                    setConfirmPassword("");
                } else {
                    Swal.fire("Error", response.data.message || "Invalid or expired token", "error");
                }
            }
        } catch (error: any) {
            let message = "Something went wrong";
            if (!error?.response) {
                message =
                    "Cannot reach the API. Check VITE_API_BASE_URL in .env.local and that the API is running.";
            } else if (error.response?.data?.message) {
                message = error.response.data.message;
            } else if (error.response?.status === 419) {
                message = "Session expired. Refresh the page and try again.";
            }
            Swal.fire("Error", message, "error");
        } finally {
            setLoading(false);
        }
    };

    const validate = () => {
        const newErrors: Record<string, string> = {};

        // Email
        if (!email.trim()) {
            newErrors.email = "Email is required";
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            newErrors.email = "Invalid email format";
        }

        // Login password
        if (mode === "login" && !password) {
            newErrors.password = "Password is required";
        }

        // if (mode === "login" && password.trim().length <= 6) {
        // Swal.fire("Error", "Password must be at least 7 characters", "error");
        //     return
        // }

        // Reset password
        if (mode === "reset") {
            if (!token) newErrors.token = "Token is required";

            if (!newPassword) {
                newErrors.newPassword = "New password is required";
            } else if (newPassword.length < 8) {
                newErrors.newPassword = "Password must be at least 8 characters";
            }

            if (!confirmPassword) {
                newErrors.confirmPassword = "Confirm password is required";
            } else if (newPassword !== confirmPassword) {
                newErrors.confirmPassword = "Passwords do not match";
            }
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };



    // Switch mode between login and forgot password
    const switchMode = (newMode: "login" | "forgot") => {
        setMode(newMode);
        setPassword(""); // clear password for security
    };

    return (
        <div className="w-screen h-screen flex items-center justify-center">
            <div className="absolute inset-0 bg-gradient-to-br from-[#eaf4ff] via-[#f5ffe5] to-[#e0f0ff]"></div>

            <div className="relative flex flex-col items-center">
                <img src={Logo} alt="Logo" className="mb-7" />

                <div className="bg-white w-[430px] p-10 rounded-[20px] shadow-lg border border-gray-200">
                    <h1 className="text-sky-500 text-3xl font-semibold font-['Poppins'] leading-[61.95px] tracking-tight mb-1">

                        {mode === "login"
                            ? "Log In"
                            : mode === "forgot"
                                ? "Forgot Password"
                                : "Reset Password"}


                    </h1>
                    <p className="justify-center text-Input-field-Text-Color text-base font-normal font-['Poppins'] leading-normal tracking-tight mb-5">
                        {mode === "login"
                            ? "Please enter your login credentials to access your account"
                            : "Enter your email to receive OTP"}
                    </p>

                    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
                        <div>
                            <TextInput
                                id="email"
                                type="email"
                                value={email}
                                placeholder="Enter email"
                                color={errors.email ? "failure" : "gray"}
                                onChange={(e) => {
                                    setEmail(e.target.value);
                                    setErrors(prev => ({ ...prev, email: "" }));
                                }}
                            />
                            {errors.email && (
                                <p className="text-red-500 text-sm mt-1">{errors.email}</p>
                            )}

                        </div>

                        {mode === "login" && (
                            <>
                                <div className="relative">
                                    <div className="flex justify-between items-center mb-1" >
                                        <Label htmlFor="password" className="text-gray-700">Password</Label>
                                        <a
                                            href="#"
                                            className="text-blue-600 text-sm hover:underline"
                                            onClick={() => switchMode("forgot")}
                                        >
                                            Forgot Password?
                                        </a>
                                    </div>
                                    <TextInput
                                        id="password"
                                        // type="password"
                                        value={password}
                                        type={showPassword ? "text" : "password"}

                                        placeholder="Enter password"
                                        color={errors.password ? "failure" : "gray"}
                                        onChange={(e) => {
                                            setPassword(e.target.value);
                                            setErrors(prev => ({ ...prev, password: "" }));
                                        }}
                                    />
                                    <div
                                        className="absolute inset-y-0 right-3 top-5 flex items-center cursor-pointer"
                                        onClick={() => setShowPassword(!showPassword)}
                                    >
                                        {showPassword ? (
                                            <HiEyeOff className="h-5 w-5 text-gray-500" />
                                        ) : (
                                            <HiEye className="h-5 w-5 text-gray-500" />
                                        )}
                                    </div>
                                    {errors.password && (
                                        <p className="text-red-500 text-sm mt-1">{errors.password}</p>
                                    )}

                                </div>
                                <div className="flex items-center gap-2">
                                    <Checkbox id="remember" checked={rememberMe} onChange={(e) => setRememberMe(e.target.checked)} />
                                    <Label htmlFor="remember" className="text-sm">Remember Me</Label>
                                </div>
                            </>
                        )}

                        {mode === "forgot" && (
                            <div className="flex justify-end">
                                <a
                                    href="#"
                                    className="text-blue-600 text-sm hover:underline"
                                    onClick={() => switchMode("login")}
                                >
                                    Back to Login
                                </a>
                            </div>
                        )}

                        {mode === "reset" && (
                            <>
                                <div>
                                    <Label>Token</Label>
                                    <TextInput
                                        value={token}
                                        placeholder="Enter OTP / Token"
                                        color={errors.token ? "failure" : "gray"}
                                        onChange={(e) => {
                                            setToken(e.target.value);
                                            setErrors(prev => ({ ...prev, token: "" }));
                                        }}
                                    />
                                    {errors.token && <p className="text-red-500 text-sm">{errors.token}</p>}

                                </div>

                                <div className="relative">
                                    <Label>New Password</Label>
                                    <TextInput
                                        type={showPassword ? "text" : "password"}
                                        value={newPassword}
                                        placeholder="New Password"
                                        color={errors.newPassword ? "failure" : "gray"}
                                        onChange={(e) => {
                                            setNewPassword(e.target.value);
                                            setErrors(prev => ({ ...prev, newPassword: "" }));
                                        }}
                                    />
                                    <div
                                        className="absolute inset-y-0 right-3 top-5 flex items-center cursor-pointer"
                                        onClick={() => setShowPassword(!showPassword)}
                                    >
                                        {showPassword ? (
                                            <HiEyeOff className="h-5 w-5 text-gray-500" />
                                        ) : (
                                            <HiEye className="h-5 w-5 text-gray-500" />
                                        )}
                                    </div>
                                    {errors.newPassword && (
                                        <p className="text-red-500 text-sm">{errors.newPassword}</p>
                                    )}

                                </div>

                                <div className="relative">
                                    <Label>Confirm Password</Label>
                                    <TextInput
                                        type={showPassword ? "text" : "password"}
                                        value={confirmPassword}
                                        placeholder="Confirm Password"
                                        color={errors.confirmPassword ? "failure" : "gray"}
                                        onChange={(e) => {
                                            setConfirmPassword(e.target.value);
                                            setErrors(prev => ({ ...prev, confirmPassword: "" }));
                                        }}
                                    />
                                    <div
                                        className="absolute inset-y-0 right-3 top-5 flex items-center cursor-pointer"
                                        onClick={() => setShowPassword(!showPassword)}
                                    >
                                        {showPassword ? (
                                            <HiEyeOff className="h-5 w-5 text-gray-500" />
                                        ) : (
                                            <HiEye className="h-5 w-5 text-gray-500" />
                                        )}
                                    </div>
                                    {errors.confirmPassword && (
                                        <p className="text-red-500 text-sm">{errors.confirmPassword}</p>
                                    )}

                                </div>
                            </>
                        )}


                        <Button
                            type="submit"
                            disabled={loading}
                            className="w-full bg-blue-600 hover:bg-blue-700 text-white text-base font-medium font-['Poppins']"
                        >
                            {mode === "login"
                                ? loading ? "Logging in..." : "Login"
                                : mode === "forgot"
                                    ? loading ? "Sending OTP..." : "Send OTP"
                                    : loading ? "Resetting..." : "Reset Password"}

                        </Button>
                    </form>
                </div>
            </div>
        </div>
    );
};

export default Login;
