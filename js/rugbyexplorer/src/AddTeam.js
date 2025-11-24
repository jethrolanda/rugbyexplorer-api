import { useState, useEffect } from "react";
import { Button, Modal, Flex, Form, Input, Select, notification } from "antd";
import { TeamOutlined } from "@ant-design/icons";

const AddTeam = ({ setClubTeams, entityOptions }) => {
  const [form] = Form.useForm();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [api, contextHolder] = notification.useNotification();
  const openNotificationWithIcon = (type, title, description) => {
    api[type]({
      title,
      description
    });
  };

  const showModal = () => {
    setIsModalOpen(true);
  };
  const handleCancel = () => {
    setIsModalOpen(false);
  };

  const onFinish = async (values) => {
    setLoading(true);
    const formData = new FormData();
    formData.append("action", "create_team");
    formData.append("data", JSON.stringify(values));

    const response = await fetch(rugbyexplorer_params.ajax_url, {
      method: "POST",
      headers: {
        "X-WP-Nonce": rugbyexplorer_params.nonce
      },
      body: formData
    });

    if (!response.ok) throw new Error("API request failed");
    const { status, data } = await response.json();
    setLoading(false);

    if (status === "success") {
      form.resetFields();
      setIsModalOpen(false);
      setClubTeams(data?.rugbyexplorer_field_club_teams);
      openNotificationWithIcon(
        "success",
        "Team Added",
        "The team has been added successfully."
      );
    } else {
      openNotificationWithIcon(
        "error",
        "Error",
        "There was an error adding the team."
      );
    }
  };

  useEffect(() => {
    form.setFieldsValue({
      rugbyexplorer_field_club_teams:
        rugbyexplorer_params?.settings?.rugbyexplorer_field_club_teams ?? []
    });
  }, []);
  return (
    <>
      {contextHolder}
      <Flex
        direction="row"
        justify="flex-end"
        align="center"
        style={{ marginBottom: "20px" }}
      >
        <Button type="primary" onClick={showModal}>
          <TeamOutlined /> Add Team
        </Button>
      </Flex>
      <Modal
        title="ADD NEW TEAM"
        closable={{ "aria-label": "Custom Close Button" }}
        open={isModalOpen}
        onCancel={handleCancel}
        onOk={() => form.submit()}
        okText={<>Submit</>}
        confirmLoading={loading}
        centered
        width={800}
      >
        <Form
          form={form}
          name="rugbyexplorer_field_club_teams"
          labelCol={{ span: 6 }}
          wrapperCol={{ span: 18 }}
          onFinish={onFinish}
        >
          <Form.Item
            label="Name"
            name="name"
            rules={[{ required: true, message: "Please input team name!" }]}
          >
            <Input />
          </Form.Item>
          <Form.Item
            label="Season"
            name="season"
            tooltip={() => "Ex: 2025"}
            rules={[
              {
                required: true,
                message: "Please input season year!"
              }
            ]}
          >
            <Input />
          </Form.Item>
          <Form.Item
            label="Entity ID"
            name="entity_id"
            tooltip={() => "Ex: jruc, jjruc, scm-jnr-rugby-union, etc."}
            rules={[{ required: true, message: "Please input entity ID!" }]}
          >
            <Select options={entityOptions} />
          </Form.Item>
          <Form.Item
            label="Competition ID"
            name="competition_id"
            rules={[
              {
                required: true,
                message: "Please input competition ID!"
              }
            ]}
          >
            <Input />
          </Form.Item>
          <Form.Item
            label="Team ID"
            name="team_id"
            rules={[{ required: true, message: "Please input team ID!" }]}
          >
            <Input />
          </Form.Item>
        </Form>
      </Modal>
    </>
  );
};
export default AddTeam;
