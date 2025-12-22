import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { Input, Button, Card, Select } from "../../shared/ui";
import { useAuth } from "../../shared/providers/AuthProvider";
import { useToast } from "../../shared/ui";
import styles from "./AuthPages.module.css";
import { useNavigate } from "react-router-dom";

const schema = z.object({
  email: z.string().email(),
  password: z.string().min(6),
  first_name: z.string().min(1),
  last_name: z.string().min(1),
  role: z.enum(["client", "proprietor"]),
});

type FormValues = z.infer<typeof schema>;

export function RegisterPage() {
  const { register: registerUser } = useAuth();
  const navigate = useNavigate();
  const { notify } = useToast();
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({ resolver: zodResolver(schema), defaultValues: { role: "client" } });

  const onSubmit = async (data: FormValues) => {
    try {
      await registerUser(data);
      navigate("/search");
    } catch (error) {
      notify({
        title: "Inscription échouée",
        description: error instanceof Error ? error.message : "Veuillez réessayer",
        variant: "error",
      });
    }
  };

  return (
    <div className={styles.wrapper}>
      <Card title="Créer un compte" subtitle="Démarrez votre stationnement en toute confiance">
        <form onSubmit={handleSubmit(onSubmit)} className={styles.form}>
          <Input label="Prénom" error={errors.first_name?.message} {...register("first_name")} />
          <Input label="Nom" error={errors.last_name?.message} {...register("last_name")} />
          <Input label="Email" type="email" error={errors.email?.message} {...register("email")} />
          <Input label="Mot de passe" type="password" error={errors.password?.message} {...register("password")} />
          <Select label="Rôle" error={errors.role?.message} {...register("role")}>
            <option value="client">Client</option>
            <option value="proprietor">Propriétaire</option>
          </Select>
          <Button type="submit" loading={isSubmitting}>
            Créer un compte
          </Button>
        </form>
      </Card>
    </div>
  );
}
