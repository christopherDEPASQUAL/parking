import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { Input, Button, Card } from "../../shared/ui";
import { useAuth } from "../../shared/providers/AuthProvider";
import { useToast } from "../../shared/ui";
import styles from "./AuthPages.module.css";
import { useNavigate } from "react-router-dom";

const schema = z.object({
  email: z.string().email(),
  password: z.string().min(6),
});

type FormValues = z.infer<typeof schema>;

export function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const { notify } = useToast();
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({ resolver: zodResolver(schema) });

  const onSubmit = async (data: FormValues) => {
    try {
      await login(data.email, data.password);
      navigate("/search");
    } catch (error) {
      notify({
        title: "Login failed",
        description: error instanceof Error ? error.message : "Please try again",
        variant: "error",
      });
    }
  };

  return (
    <div className={styles.wrapper}>
      <Card title="Welcome back" subtitle="Login to continue">
        <form onSubmit={handleSubmit(onSubmit)} className={styles.form}>
          <Input
            label="Email"
            type="email"
            placeholder="name@email.com"
            error={errors.email?.message}
            {...register("email")}
          />
          <Input
            label="Password"
            type="password"
            placeholder="Your password"
            error={errors.password?.message}
            {...register("password")}
          />
          <Button type="submit" loading={isSubmitting}>
            Login
          </Button>
        </form>
      </Card>
    </div>
  );
}
