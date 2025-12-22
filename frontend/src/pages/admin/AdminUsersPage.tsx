import { Card, Table } from "../../shared/ui";

export function AdminUsersPage() {
  return (
    <Card title="Utilisateurs" subtitle="Liste en lecture seule">
      <p>Connectez l’endpoint admin pour afficher les données en temps réel.</p>
      <Table columns={["Utilisateur", "Email", "Rôle"]}>
        <tr>
          <td>Exemple</td>
          <td>exemple@email.com</td>
          <td>client</td>
        </tr>
      </Table>
    </Card>
  );
}
