import { Card, Table } from "../../shared/ui";

export function AdminUsersPage() {
  return (
    <Card title="Users" subtitle="Read-only users list">
      <p>Wire the admin users endpoint to display live data.</p>
      <Table columns={["User", "Email", "Role"]}>
        <tr>
          <td>Sample</td>
          <td>sample@email.com</td>
          <td>client</td>
        </tr>
      </Table>
    </Card>
  );
}
