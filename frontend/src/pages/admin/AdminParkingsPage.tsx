import { Card, Table } from "../../shared/ui";

export function AdminParkingsPage() {
  return (
    <Card title="Parkings" subtitle="Liste en lecture seule">
      <p>Connectez l’endpoint admin pour afficher les données en temps réel.</p>
      <Table columns={["Parking", "Adresse", "Propriétaire"]}>
        <tr>
          <td>Parking exemple</td>
          <td>1 rue principale</td>
          <td>owner@email.com</td>
        </tr>
      </Table>
    </Card>
  );
}
