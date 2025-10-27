import { useState, useEffect } from "react";
import { Button, Space, Popconfirm, notification } from "antd";

const App = () => {
  const [loading, setLoading] = useState(false);
  const [deleting, setDeleting] = useState(false);

  const [api, contextHolder] = notification.useNotification();
  const openNotificationWithIcon = (type) => {
    api[type]({
      message: "Import Successful!",
      description: `Competitions imported successfully!`
    });
  };

  const importData = () => {
    async function importing() {
      try {
        setLoading(true);

        // const promises = fusesport_params?.fusesport_competition_ids.map(
        //   async (id) => {
        const response = await fetch(fusesport_params.ajax_url, {
          method: "POST",
          headers: {
            "X-WP-Nonce": fusesport_params.nonce,
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
          },
          body: new URLSearchParams({
            action: "fusesport_api"
          })
        });

        if (!response.ok) throw new Error("API request failed");
        const { status, data } = await response.json();

        console.log(data);
        if (status === "success") {
          openNotificationWithIcon(
            "success"
            // data?.["rugby-schedule"]?.[0]?.["competitions"]?.[0]?.["full_name"]
          );
        }
        // }
        // );

        // await Promise.all(promises);
      } catch (error) {
        console.error("Error fetching orders:", error);
      } finally {
        setLoading(false);
      }
    }

    importing();
  };

  const deleteEvents = () => {
    async function deleting() {
      try {
        setDeleting(true);

        const response = await fetch(fusesport_params.ajax_url, {
          method: "POST",
          headers: {
            "X-WP-Nonce": fusesport_params.nonce,
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
          },
          body: new URLSearchParams({
            action: "delete_events"
          })
        });

        if (!response.ok) throw new Error("API request failed");
        const { status, data } = await response.json();

        console.log(data);
        if (status === "success") {
        }
      } catch (error) {
        console.error("Error fetching orders:", error);
      } finally {
        setDeleting(false);
      }
    }

    deleting();
  };

  return (
    <Space size="large" direction="vertical">
      {contextHolder}
      <div>
        <Button onClick={importData} loading={loading}>
          Import Data
        </Button>
        <p>Import data from FuseSport API to SportsPress</p>
      </div>
      <div>
        <Popconfirm
          title="Delete all events"
          description="Are you sure to delete all events?"
          onConfirm={deleteEvents}
          okText="Yes"
          cancelText="No"
        >
          <Button loading={deleting}>Delete Events</Button>
        </Popconfirm>

        <p>
          Delete all events. Use this if you want to import again all events.
        </p>
      </div>
    </Space>
  );
};
export default App;
