import { Space, Table, Button, Popconfirm, notification } from "antd";

const showTotal = (total) => `Total ${total} items`;
export default function TeamTable({ data, setClubTeams }) {
  const [api, contextHolder] = notification.useNotification();
  const openNotificationWithIcon = (type, title, description) => {
    api[type]({
      title,
      description
    });
  };

  const deleteItem = async (record) => {
    console.log(record);
    const formData = new FormData();
    formData.append("action", "delete_team");
    formData.append("data", JSON.stringify(record));

    const response = await fetch(rugbyexplorer_params.ajax_url, {
      method: "POST",
      headers: {
        "X-WP-Nonce": rugbyexplorer_params.nonce
      },
      body: formData
    });

    if (!response.ok) throw new Error("API request failed");
    const { status, data } = await response.json();

    if (status === "success") {
      setClubTeams(data?.rugbyexplorer_field_club_teams);
      openNotificationWithIcon(
        "success",
        "Team Deleted",
        "The team has been deleted successfully."
      );
    } else {
      openNotificationWithIcon(
        "error",
        "Error",
        "There was an error deleting the team."
      );
    }
  };
  const columns = [
    {
      title: "Name",
      dataIndex: "name",
      key: "name",
      sorter: (a, b) => a.name.length - b.name.length
    },
    {
      title: "Season",
      dataIndex: "season",
      key: "season",
      sorter: (a, b) => a.season - b.season
    },
    {
      title: "Entity ID",
      dataIndex: "entity_id",
      key: "entity_id"
    },
    {
      title: "Competition ID",
      dataIndex: "competition_id",
      key: "competition_id"
    },
    {
      title: "Team ID",
      dataIndex: "team_id",
      key: "team_id"
    },
    {
      title: "Action",
      key: "action",
      render: (_, record) => (
        <Space size="middle">
          <Popconfirm
            placement="right"
            title="Are you sure to delete this team?"
            description="Delete this team"
            okText="Yes"
            cancelText="No"
            onConfirm={() => deleteItem(record)}
          >
            <Button color="danger" variant="outlined">
              Delete
            </Button>
          </Popconfirm>
        </Space>
      )
    }
  ];

  return (
    <>
      {contextHolder}
      <Table
        columns={columns}
        dataSource={[...data].reverse()}
        pagination={{
          showTotal: showTotal,
          align: "center"
        }}
      />
    </>
  );
}
