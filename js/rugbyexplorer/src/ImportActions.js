import { useState, useEffect } from "react";
import { Button, Space, Popconfirm, notification, Card } from "antd";

const ImportActions = () => {
  const [loading, setLoading] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [eventStatus, setEventStatus] = useState([]);

  const [api, contextHolder] = notification.useNotification();
  const openNotificationWithIcon = (type, msg, desc) => {
    api[type]({
      message: msg,
      description: desc
    });
  };

  const importData = async () => {
    try {
      setLoading(true);
      setEventStatus([]);
      // const response = await fetch(rugbyexplorer_params.ajax_url, {
      //   method: "POST",
      //   headers: {
      //     "X-WP-Nonce": rugbyexplorer_params.nonce,
      //     "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
      //   },
      //   body: new URLSearchParams({
      //     action: "rugbyexplorer_api"
      //   })
      // });

      // if (!response.ok) throw new Error("API request failed");
      // const { status, data } = await response.json();

      // console.log(data);
      // if (status === "success") {
      //   openNotificationWithIcon(
      //     "success",
      //     "Import Successful!",
      //     "Competitions imported successfully!"
      //     // data?.["rugby-schedule"]?.[0]?.["competitions"]?.[0]?.["full_name"]
      //   );
      // }

      // const allRequests = Object.entries(
      //   rugbyexplorer_params?.settings?.rugbyexplorer_field_club_teams
      // ).reduce(async (promise, [key, team]) => {
      //   return promise.then(async () => {
      //     const formData = new FormData();
      //     formData.append("action", "rugbyexplorer_api");
      //     formData.append("season", team?.season);
      //     formData.append("competition_id", team?.competition_id);
      //     formData.append("team_id", team?.team_id);
      //     formData.append("entity_id", team?.entity_id);

      //     const res = await fetch(rugbyexplorer_params.ajax_url, {
      //       method: "POST",
      //       headers: {
      //         "X-WP-Nonce": rugbyexplorer_params.nonce
      //       },
      //       body: formData
      //     });
      //     const data = await res.json();
      //     if (data.status === "success") {
      //       console.log(eventStatus);
      //       setEventStatus((prev) => [
      //         ...prev,
      //         { name: team?.name, stats: data?.data?.event_status }
      //       ]);
      //     }
      //   });
      // }, Promise.resolve());

      // // 👇 Detect when all are done
      // allRequests.then(() => {
      //   setLoading(false);
      // });

      const teams = Object.entries(
        rugbyexplorer_params?.settings?.rugbyexplorer_field_club_teams || []
      );

      for (const [key, team] of teams) {
        const formData = new FormData();
        formData.append("action", "rugbyexplorer_api");
        formData.append("season", team?.season);
        formData.append("competition_id", team?.competition_id);
        formData.append("team_id", team?.team_id);
        formData.append("entity_id", team?.entity_id);

        try {
          const res = await fetch(rugbyexplorer_params.ajax_url, {
            method: "POST",
            headers: { "X-WP-Nonce": rugbyexplorer_params.nonce },
            body: formData
          });

          const data = await res.json();

          if (data.status === "success") {
            setEventStatus((prev) => [
              ...prev,
              { name: team?.name, stats: data?.data?.event_status }
            ]);
          }
        } catch (err) {
          console.error("Error fetching team", team?.name, err);
        }
      }

      setLoading(false);
    } catch (error) {
      console.error("Error fetching orders:", error);
    }
  };

  const deleteEvents = () => {
    async function deleting() {
      try {
        setDeleting(true);

        const response = await fetch(rugbyexplorer_params.ajax_url, {
          method: "POST",
          headers: {
            "X-WP-Nonce": rugbyexplorer_params.nonce,
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
          },
          body: new URLSearchParams({
            action: "delete_events"
          })
        });

        if (!response.ok) throw new Error("API request failed");
        const { status, data } = await response.json();

        if (status === "success") {
          openNotificationWithIcon(
            "success",
            "Deletion Successful!",
            "Events, teams, seasons, leagues and venues have been successfully!"
            // data?.["rugby-schedule"]?.[0]?.["competitions"]?.[0]?.["full_name"]
          );
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
    <>
      <Card size="small" title="Import" style={{ marginBottom: "16px" }}>
        <Space size="large" direction="vertical">
          {contextHolder}
          <div>
            <Button onClick={importData} loading={loading}>
              Import Data
            </Button>
            <p>
              Import data from RugbyExplorer API to SportsPress. Import Fixtures
              and Results.
            </p>
          </div>
          <div>
            <Popconfirm
              title="Delete all events"
              description="Are you sure to delete all events, teams, seasons, leagues and venues?"
              onConfirm={deleteEvents}
              okText="Yes"
              cancelText="No"
            >
              <Button loading={deleting}>Delete Events</Button>
            </Popconfirm>

            <p>
              Delete all events, teams, seasons, leagues and venues. Use this if
              you want to import again.
            </p>
          </div>
        </Space>
      </Card>
      {/* <div>
        <div>
          {eventStatus.map((status, i) => (
            <div key={i}>
              <b>{status?.name}</b>
              {status?.stats.map((stats, s) => (
                <div key={s}>
                  <p>Created: {stats?.created}</p>
                  <p>Updated: {stats?.updated}</p>
                  <p>Failed: {stats?.failed}</p>
                </div>
              ))}
            </div>
          ))}
        </div>
      </div> */}
      <ul>
        {eventStatus.map((status, index) => (
          <li key={index}>
            <h4>
              <strong>{status.name}</strong>
            </h4>
            <div>
              <p>
                Created: <b>{status?.stats?.created}</b>
              </p>
              <p>
                Updated: <b>{status?.stats?.updated}</b>
              </p>
              <p>
                Failed: <b>{status?.stats?.failed}</b>
              </p>
              <p>
                Time Taken: <b>{status?.stats?.time}s</b>
              </p>
            </div>
          </li>
        ))}
      </ul>
    </>
  );
};
export default ImportActions;
