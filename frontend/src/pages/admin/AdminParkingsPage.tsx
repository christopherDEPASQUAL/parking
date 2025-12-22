import { Card, Table } from "../../shared/ui";

export function AdminParkingsPage() {
  return (
    <Card title="Parkings" subtitle="Read-only parkings list">
      <p>Wire the admin parkings endpoint to display live data.</p>
      <Table columns={["Parking", "Address", "Owner"]}>
        <tr>
          <td>Sample Parking</td>
          <td>1 Main Street</td>
          <td>owner@email.com</td>
        </tr>
      </Table>
    </Card>
  );
}
